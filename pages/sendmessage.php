<?php

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'User is not authenticated.'
    ]);

    exit;
}

$senderId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET ACTION
|--------------------------------------------------------------------------
*/

$action = $_POST['action'] ?? '';


try {

  
    /*
    |--------------------------------------------------------------------------
    | SEND MESSAGE
    |--------------------------------------------------------------------------
    */

    if ($action === 'send_message') {
        $receiverId = (int) ($_POST['receiver_id'] ?? 0);
        $message = trim(
            $_POST['message'] ?? ''
        );

        $messageType =
            $_POST['message_type'] ?? 'text';

        $replyTo = (int) (
            $_POST['reply_to'] ?? 0
        );
        $attachmentName =
            trim(
                $_POST['attachment_name'] ?? ''
            );

        $attachmentData =
            $_POST['attachment_data'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | Validate receiver
        |--------------------------------------------------------------------------
        */

        if ($receiverId <= 0) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid receiver.'
            ]);

            exit;
        }


        if ($receiverId === $senderId) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'You cannot send a message to yourself.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Check receiver exists
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $receiverId
        ]);

        if (!$stmt->fetch()) {

            echo json_encode([
                'success' => false,
                'message' => 'Receiver does not exist.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Allowed message types
        |--------------------------------------------------------------------------
        */

        $allowedTypes = [
            'text',
            'image',
            'file',
            'audio',
            'video'
        ];

        if (
            !in_array(
                $messageType,
                $allowedTypes,
                true
            )
        ) {

            $messageType = 'text';
        }


        /*
        |--------------------------------------------------------------------------
        | Make sure something is being sent
        |--------------------------------------------------------------------------
        */

        if (
            $message === '' &&
            empty($attachmentData)
        ) {

            echo json_encode([
                'success' => false,
                'message' => 'Message cannot be empty.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate message length
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen($message) > 10000
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Message is too long.'
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate reply
        |--------------------------------------------------------------------------
        */

        if ($replyTo > 0) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM chat_messages
                WHERE id = ?
                AND (
                    sender_id = ?
                    OR receiver_id = ?
                )
                LIMIT 1
            ");

            $stmt->execute([
                $replyTo,
                $senderId,
                $senderId
            ]);

            if (!$stmt->fetch()) {

                $replyTo = null;
            }

        } else {

            $replyTo = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Attachment size protection
        |--------------------------------------------------------------------------
        */

        if (
            !empty($attachmentData)
        ) {

            /*
             * Approximately 10 MB maximum.
             */

            if (
                strlen($attachmentData)
                > 14 * 1024 * 1024
            ) {

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Attachment is too large.'
                ]);

                exit;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT MESSAGE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO chat_messages
            (
                sender_id,
                receiver_id,
                message,
                message_type,
                attachment_name,
                attachment_data,
                reply_to,
                delivered_at
            )
            VALUES
            (
                :sender_id,
                :receiver_id,
                :message,
                :message_type,
                :attachment_name,
                :attachment_data,
                :reply_to,
                NOW()
            )
        ");


        $stmt->execute([

            ':sender_id' =>
                $senderId,

            ':receiver_id' =>
                $receiverId,

            ':message' =>
                $message !== ''
                    ? $message
                    : null,

            ':message_type' =>
                $messageType,

            ':attachment_name' =>
                $attachmentName !== ''
                    ? $attachmentName
                    : null,

            ':attachment_data' =>
                !empty($attachmentData)
                    ? $attachmentData
                    : null,

            ':reply_to' =>
                $replyTo ?: null
        ]);


        /*
        |--------------------------------------------------------------------------
        | GET NEW MESSAGE ID
        |--------------------------------------------------------------------------
        */

        $messageId =
            $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | RETURN MESSAGE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                sender_id,
                receiver_id,
                message,
                message_type,
                attachment_name,
                attachment_data,
                reply_to,
                reaction,
                is_edited,
                is_deleted,
                delivered_at,
                seen_at,
                created_at
            FROM chat_messages
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $messageId
        ]);

        $newMessage =
            $stmt->fetch(PDO::FETCH_ASSOC);


        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully.',
            'message_id' => $messageId,
            'data' => $newMessage
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | MARK MESSAGE AS SEEN
    |--------------------------------------------------------------------------
    */

    if ($action === 'mark_seen') {

        $otherUserId =
            (int) ($_POST['user_id'] ?? 0);


        if ($otherUserId <= 0) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid user.'
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE chat_messages

            SET
                seen_at = NOW(),
                delivered_at =
                    COALESCE(
                        delivered_at,
                        NOW()
                    )

            WHERE
                sender_id = ?

                AND receiver_id = ?

                AND seen_at IS NULL
        ");


        $stmt->execute([
            $otherUserId,
            $senderId
        ]);


        echo json_encode([
            'success' => true,
            'message' =>
                'Messages marked as seen.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | TYPING INDICATOR
    |--------------------------------------------------------------------------
    */

    if ($action === 'typing') {

        $typingTo =
            (int) ($_POST['typing_to'] ?? 0);

        $value =
            ($_POST['value'] ?? '0') === '1'
                ? 1
                : 0;



        $stmt = $pdo->prepare("
            INSERT INTO chat_user_status
            (
                user_id,
                last_seen,
                is_online,
                is_typing,
                typing_to
            )

            VALUES
            (
                ?,
                NOW(),
                1,
                ?,
                ?
            )

            ON DUPLICATE KEY UPDATE
                last_seen = NOW(),
                is_online = 1,
                is_typing = VALUES(is_typing),
                typing_to = VALUES(typing_to)
        ");


        $stmt->execute([
            $senderId,
            $value,
            $value
                ? $typingTo
                : null
        ]);


        echo json_encode([
            'success' => true
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT MESSAGE
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit_message') {

        $messageId =
            (int) ($_POST['message_id'] ?? 0);

        $newMessage =
            trim(
                $_POST['message'] ?? ''
            );


        if (
            $messageId <= 0 ||
            $newMessage === ''
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Invalid message.'
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE chat_messages

            SET
                message = ?,
                is_edited = 1

            WHERE
                id = ?

                AND sender_id = ?

                AND is_deleted = 0
        ");


        $stmt->execute([
            $newMessage,
            $messageId,
            $senderId
        ]);


        echo json_encode([
            'success' => true,
            'message' =>
                'Message updated.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE MESSAGE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete_message') {

        $messageId =
            (int) ($_POST['message_id'] ?? 0);


        $stmt = $pdo->prepare("
            UPDATE chat_messages

            SET
                message = NULL,
                attachment_name = NULL,
                attachment_data = NULL,
                is_deleted = 1

            WHERE
                id = ?

                AND sender_id = ?
        ");


        $stmt->execute([
            $messageId,
            $senderId
        ]);


        echo json_encode([
            'success' => true,
            'message' =>
                'Message deleted.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REACTION
    |--------------------------------------------------------------------------
    */

    if ($action === 'react_message') {

        $messageId =
            (int) ($_POST['message_id'] ?? 0);

        $reaction =
            trim(
                $_POST['reaction'] ?? ''
            );


        if (
            mb_strlen($reaction) > 20
        ) {

            $reaction =
                mb_substr(
                    $reaction,
                    0,
                    20
                );
        }


        $stmt = $pdo->prepare("
            UPDATE chat_messages

            SET reaction = ?

            WHERE
                id = ?

                AND (
                    sender_id = ?
                    OR receiver_id = ?
                )
        ");


        $stmt->execute([
            $reaction ?: null,
            $messageId,
            $senderId,
            $senderId
        ]);


        echo json_encode([
            'success' => true
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | SET USER OFFLINE
    |--------------------------------------------------------------------------
    */

    if ($action === 'offline') {

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_user_status (

                user_id INT PRIMARY KEY,

                last_seen DATETIME NULL,

                is_online TINYINT(1)
                    DEFAULT 0,

                is_typing TINYINT(1)
                    DEFAULT 0,

                typing_to INT NULL

            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
        ");


        $stmt = $pdo->prepare("
            INSERT INTO chat_user_status
            (
                user_id,
                last_seen,
                is_online,
                is_typing,
                typing_to
            )

            VALUES
            (
                ?,
                NOW(),
                0,
                0,
                NULL
            )

            ON DUPLICATE KEY UPDATE
                last_seen = NOW(),

                is_online = 0,

                is_typing = 0,

                typing_to = NULL
        ");


        $stmt->execute([
            $senderId
        ]);


        echo json_encode([
            'success' => true
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | INVALID ACTION
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Database error while sending message.',
        'error' =>
            $e->getMessage()
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'An unexpected error occurred.',
        'error' =>
            $e->getMessage()
    ]);
}