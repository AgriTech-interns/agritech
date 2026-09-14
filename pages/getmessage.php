<?php

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'You are not authenticated.'
    ]);

    exit;
}

$userId = (int) $_SESSION['user_id'];

try {

    $action =
        $_GET['action'] ??
        $_POST['action'] ??
        '';


    /*
    |--------------------------------------------------------------------------
    | GET MESSAGES
    |--------------------------------------------------------------------------
    */

    if ($action === 'get_messages') {

        $otherUserId =
            (int) ($_GET['user_id'] ?? 0);

        $lastId =
            (int) ($_GET['last_id'] ?? 0);


        if ($otherUserId <= 0) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid user.'
            ]);

            exit;
        }


        /*
        | If last_id = 0, retrieve conversation.
        | Otherwise retrieve only newer messages.
        */

        if ($lastId > 0) {

            $stmt = $pdo->prepare("
                SELECT
                    m.*,

                    CASE
                        WHEN m.reply_to IS NOT NULL
                        THEN rm.message
                        ELSE NULL
                    END AS reply_to_text,

                    CASE
                        WHEN m.reply_to IS NOT NULL
                        THEN
                            CASE
                                WHEN rm.sender_id = ?
                                THEN 'You'
                                ELSE COALESCE(u_reply.fullname, 'User')
                            END
                        ELSE NULL
                    END AS reply_to_name

                FROM chat_messages m

                LEFT JOIN chat_messages rm
                    ON rm.id = m.reply_to

                LEFT JOIN users u_reply
                    ON u_reply.user_id = rm.sender_id

                WHERE
                    m.id > ?
                    AND (
                        (
                            m.sender_id = ?
                            AND m.receiver_id = ?
                        )
                        OR
                        (
                            m.sender_id = ?
                            AND m.receiver_id = ?
                        )
                    )

                ORDER BY m.id ASC
            ");

            $stmt->execute([
                $userId,
                $lastId,
                $userId,
                $otherUserId,
                $otherUserId,
                $userId
            ]);

        } else {

            $stmt = $pdo->prepare("
                SELECT
                    m.*,

                    CASE
                        WHEN m.reply_to IS NOT NULL
                        THEN rm.message
                        ELSE NULL
                    END AS reply_to_text,

                    CASE
                        WHEN m.reply_to IS NOT NULL
                        THEN
                            CASE
                                WHEN rm.sender_id = ?
                                THEN 'You'
                                ELSE COALESCE(u_reply.fullname, 'User')
                            END
                        ELSE NULL
                    END AS reply_to_name

                FROM chat_messages m

                LEFT JOIN chat_messages rm
                    ON rm.id = m.reply_to

                LEFT JOIN users u_reply
                    ON u_reply.user_id = rm.sender_id

                WHERE
                    (
                        m.sender_id = ?
                        AND m.receiver_id = ?
                    )
                    OR
                    (
                        m.sender_id = ?
                        AND m.receiver_id = ?
                    )

                ORDER BY m.id ASC
            ");

            $stmt->execute([
                $userId,
                $userId,
                $otherUserId,
                $otherUserId,
                $userId
            ]);
        }


        $messages =
            $stmt->fetchAll(PDO::FETCH_ASSOC);


        /*
        | Mark received messages as delivered.
        */

        $stmt = $pdo->prepare("
            UPDATE chat_messages
            SET delivered_at = NOW()
            WHERE
                sender_id = ?
                AND receiver_id = ?
                AND delivered_at IS NULL
        ");

        $stmt->execute([
            $otherUserId,
            $userId
        ]);


        /*
        | Status
        */

        $stmt = $pdo->prepare("
            SELECT
                is_online,
                last_seen,
                is_typing,
                typing_to
            FROM chat_user_status
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $otherUserId
        ]);

        $status =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$status) {

            $status = [
                'is_online' => 0,
                'last_seen' => null,
                'is_typing' => 0,
                'typing_to' => null
            ];
        }


        /*
        | Update our own heartbeat.
        */

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
            (?, NOW(), 1, 0, NULL)
            ON DUPLICATE KEY UPDATE
                last_seen = NOW(),
                is_online = 1
        ");

        $stmt->execute([
            $userId
        ]);


        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'status' => $status
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GET STATUS
    |--------------------------------------------------------------------------
    */

    if ($action === 'status') {

        $otherUserId =
            (int) ($_GET['user_id'] ?? 0);


        $stmt = $pdo->prepare("
            SELECT
                is_online,
                last_seen,
                is_typing,
                typing_to
            FROM chat_user_status
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $otherUserId
        ]);

        $status =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$status) {

            $status = [
                'is_online' => 0,
                'last_seen' => null,
                'is_typing' => 0,
                'typing_to' => null
            ];
        }


        /*
        | Consider a user offline if the browser
        | has not sent a heartbeat for 15 seconds.
        */

        if (
            !empty($status['last_seen'])
        ) {

            $lastSeen =
                strtotime(
                    $status['last_seen']
                );

            if (
                time() - $lastSeen >
                15
            ) {

                $status['is_online'] = 0;
            }
        }


        echo json_encode([
            'success' => true,
            'status' => $status
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UNREAD COUNTS
    |--------------------------------------------------------------------------
    */

    if ($action === 'unread') {

        $stmt = $pdo->prepare("
            SELECT
                sender_id,
                COUNT(*) AS unread_count
            FROM chat_messages
            WHERE
                receiver_id = ?
                AND seen_at IS NULL
            GROUP BY sender_id
        ");

        $stmt->execute([
            $userId
        ]);

        echo json_encode([
            'success' => true,
            'unread' =>
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                )
        ]);

        exit;
    }


    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error.',
        'error' => $e->getMessage()
    ]);
}