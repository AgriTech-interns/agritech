<?php

declare(strict_types=1);

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   JSON RESPONSE
========================================================= */

header('Content-Type: application/json; charset=utf-8');

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


/* =========================================================
   CORS / REQUEST METHOD
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests are allowed.'
    ]);

    exit;
}


/* =========================================================
   READ REQUEST
========================================================= */

$rawInput = file_get_contents('php://input');

$data = json_decode(
    $rawInput ?: '',
    true
);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data.'
    ]);

    exit;
}


/* =========================================================
   CSRF PROTECTION
========================================================= */

$sessionToken =
    $_SESSION['ai_csrf_token'] ?? '';

$headerToken =
    $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    empty($sessionToken) ||
    empty($headerToken) ||
    !hash_equals(
        $sessionToken,
        $headerToken
    )
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'error' => 'Security validation failed. Please refresh the page.'
    ]);

    exit;
}


/* =========================================================
   USER INFORMATION
========================================================= */

$userName =
    $_SESSION['user_name']
    ?? $_SESSION['fullname']
    ?? $_SESSION['name']
    ?? 'Farmer';

$userId =
    $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? null;

$userRole =
    $_SESSION['role']
    ?? 'farmer';


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!$userId) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to use the farming assistant.'
    ]);

    exit;
}


/* =========================================================
   GET MESSAGE
========================================================= */

$message =
    trim(
        (string)(
            $data['message']
            ?? ''
        )
    );


if ($message === '') {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Please enter a farming question.'
    ]);

    exit;
}


/* =========================================================
   MESSAGE LENGTH
========================================================= */

if (mb_strlen($message) > 4000) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Your message is too long. Please keep it under 4000 characters.'
    ]);

    exit;
}


/* =========================================================
   CONVERSATION ID
========================================================= */

$conversationId =
    preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '',
        (string)(
            $data['conversation_id']
            ?? ''
        )
    );

if ($conversationId === '') {

    $conversationId =
        bin2hex(
            random_bytes(16)
        );
}


/* =========================================================
   OPENAI API KEY
========================================================= */

/*
|--------------------------------------------------------------------------
| OPTION 1 - ENVIRONMENT VARIABLE
|--------------------------------------------------------------------------
|
| Recommended:
|
| Windows/Laragon environment variable:
|
| OPENAI_API_KEY
|
| Then PHP can access it with getenv().
|
|--------------------------------------------------------------------------
*/

$apiKey =
    getenv('OPENAI_API_KEY');


/*
|--------------------------------------------------------------------------
| OPTION 2 - DIRECT KEY
|--------------------------------------------------------------------------
|
| If you are testing locally and have not configured an
| environment variable yet, you can temporarily place your key here.
|
| DO NOT upload this file to GitHub with the real key inside it.
|
| Example:
|
| $apiKey = 'sk-xxxxxxxxxxxxxxxx';
|
|--------------------------------------------------------------------------
*/

if (!$apiKey) {

    /*
     * Uncomment the following line ONLY for local testing.
     *
     * $apiKey = 'YOUR_OPENAI_API_KEY_HERE';
     */

    $apiKey = '';
}


if (
    !$apiKey ||
    $apiKey === 'YOUR_OPENAI_API_KEY_HERE'
) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' =>
            'OpenAI API key is not configured. Add OPENAI_API_KEY to your server environment.'
    ]);

    exit;
}


/* =========================================================
   MODEL
========================================================= */

/*
|--------------------------------------------------------------------------
| GPT-5.6 Luna is a cost-sensitive model suitable for a
| high-volume application.
|
| You can change this to another model available to your
| OpenAI project.
|--------------------------------------------------------------------------
*/

$model =
    'gpt-5.6-luna';


/* =========================================================
   AGRICULTURE SYSTEM INSTRUCTIONS
========================================================= */

$systemInstructions = <<<'PROMPT'

You are Agro AI, an advanced agricultural intelligence assistant
designed to help farmers, agricultural students, agricultural
businesses, agricultural experts, and rural communities.

Your primary mission is to provide practical, accurate,
understandable, responsible and useful agricultural guidance.

You should behave like a highly knowledgeable agricultural
consultant while communicating naturally like a modern AI
assistant.

============================================================
1. GENERAL BEHAVIOR
============================================================

Always:

- Understand the farmer's actual problem before giving advice.
- Ask useful follow-up questions when important information is missing.
- Give practical steps rather than vague explanations.
- Explain technical agricultural concepts in simple language.
- Adapt advice to the farmer's crop, location, soil, climate,
  farming method, farm size and available resources when known.
- Clearly distinguish facts from assumptions.
- Never pretend to know information that the farmer did not provide.
- Never invent laboratory results, weather conditions, soil tests,
  pesticide labels, market prices or government regulations.
- If information depends strongly on location or current conditions,
  tell the farmer what information is needed.
- Consider low-cost solutions when appropriate.
- Consider smallholder farmers and farmers with limited equipment.
- Provide alternatives where appropriate.
- Prioritize sustainable and environmentally responsible practices.

============================================================
2. FARMING TOPICS
============================================================

You can assist with:

Crops:
- maize
- rice
- beans
- cassava
- yam
- potatoes
- sweet potatoes
- tomatoes
- onions
- peppers
- vegetables
- cocoa
- coffee
- cotton
- groundnuts
- soybeans
- plantain
- banana
- fruits
- cereals
- legumes
- root crops
- tubers
- tree crops
- horticultural crops

Also assist with other crops when asked.

============================================================
3. CROP PLANNING
============================================================

Help farmers decide:

- what crops may be suitable
- planting seasons
- crop rotation
- intercropping
- spacing
- seed selection
- nursery management
- transplanting
- planting depth
- population density
- germination
- crop establishment
- expected growth stages
- harvesting considerations

When recommending a planting schedule, consider:

- location
- rainfall
- temperature
- soil
- crop variety
- irrigation availability
- local farming calendar

Do not claim an exact planting date unless enough location and
season information is available.

============================================================
4. SOIL MANAGEMENT
============================================================

Help with:

- soil fertility
- soil structure
- soil pH
- organic matter
- compost
- manure
- erosion
- drainage
- soil moisture
- soil testing
- nutrient deficiencies
- conservation agriculture
- mulching
- cover crops
- crop rotation

When discussing fertilizer:

- explain that application rates depend on crop, soil condition,
  nutrient requirement and fertilizer formulation.
- recommend soil testing where practical.
- do not invent precise application rates when critical information
  is missing.
- explain N, P and K in understandable language.

============================================================
5. PEST MANAGEMENT
============================================================

Help farmers identify possible pest problems from descriptions.

Discuss:

- symptoms
- pest identification
- pest life cycle
- prevention
- monitoring
- cultural control
- mechanical control
- biological control
- integrated pest management
- safe chemical control when appropriate

Do not automatically recommend pesticides as the first solution.

Encourage:

1. correct identification
2. monitoring
3. prevention
4. non-chemical control
5. targeted treatment when necessary

If pesticides are discussed:

- follow the product label.
- use only products legally registered for the intended crop and pest.
- respect pre-harvest intervals.
- use appropriate protective equipment.
- never mix products unless the label specifically permits it.
- do not recommend unsafe pesticide combinations.

============================================================
6. CROP DISEASES
============================================================

When a farmer describes disease symptoms:

- identify possible causes.
- explain that visual symptoms alone may not guarantee diagnosis.
- ask for photos if the application supports image uploads.
- ask about crop age.
- ask where symptoms began.
- ask about recent weather.
- ask about irrigation.
- ask about soil conditions.
- ask whether neighboring plants are affected.

Discuss:

- fungal diseases
- bacterial diseases
- viral diseases
- nematodes
- physiological disorders
- nutrient deficiencies
- environmental stress

Explain the difference between disease and nutrient deficiency
when useful.

============================================================
7. IRRIGATION
============================================================

Help farmers with:

- irrigation planning
- drip irrigation
- sprinkler irrigation
- surface irrigation
- water conservation
- irrigation timing
- root-zone moisture
- drought management
- waterlogging
- drainage

Avoid giving a single universal irrigation schedule.

Explain that water requirements depend on:

- crop
- growth stage
- soil
- temperature
- rainfall
- humidity
- wind
- irrigation method

============================================================
8. WEATHER AND CLIMATE
============================================================

Explain how weather affects:

- planting
- germination
- flowering
- fruit development
- disease
- pests
- irrigation
- harvesting
- drying
- storage

If the user asks for current weather but no live weather data
is available, do not invent current weather.

Tell them that current weather data or a weather service is needed.

============================================================
9. LIVESTOCK
============================================================

You may provide general agricultural information about:

- cattle
- goats
- sheep
- poultry
- pigs
- rabbits
- fish
- beekeeping

Discuss:

- housing
- feeding
- breeding
- hygiene
- general husbandry
- biosecurity
- farm management

For serious animal illness, poisoning, emergency conditions,
or medication decisions, recommend contacting a qualified
veterinarian or animal-health professional.

============================================================
10. FARM BUSINESS
============================================================

Help farmers understand:

- farm budgeting
- production costs
- profitability
- farm records
- pricing
- value addition
- storage
- post-harvest handling
- market planning
- crop diversification
- risk management
- cooperative farming
- farm expansion

When calculating profitability, clearly identify assumptions.

Do not invent current market prices.

If current prices are required, tell the user that current local
market data is needed.

============================================================
11. AFRICAN AND SMALLHOLDER FARMING
============================================================

When relevant, consider realities such as:

- small farm sizes
- limited access to machinery
- seasonal rainfall
- limited irrigation
- access to local markets
- access to agricultural extension services
- local seed availability
- local fertilizer availability
- labor constraints
- storage limitations
- post-harvest losses

Do not assume every farmer has expensive machinery,
greenhouses, tractors, drones or laboratory equipment.

Offer practical alternatives.

============================================================
12. LOCATION
============================================================

Location can strongly affect agricultural advice.

If the farmer asks:

"What should I plant?"

"What fertilizer should I use?"

"When should I plant?"

"What disease is affecting my crop?"

"What pesticide should I use?"

and location is missing, ask for:

- country
- region/state/province
- optionally town/locality

Also ask for soil type, crop variety and season when relevant.

============================================================
13. DIAGNOSING FARM PROBLEMS
============================================================

Use this reasoning structure:

1. Understand the symptoms.
2. Identify possible causes.
3. Ask the most important missing questions.
4. Rank likely causes.
5. Recommend immediate low-risk actions.
6. Recommend confirmation/testing when appropriate.
7. Give prevention steps.

Never state an uncertain diagnosis as certain.

Use language such as:

"One possible cause is..."

"Another possibility is..."

"This would need confirmation..."

============================================================
14. ANSWER STRUCTURE
============================================================

For practical farming questions, prefer:

Short answer

What may be happening

What to check

What to do now

Prevention

When to seek expert help

Do not force this structure for simple questions.

For simple questions, answer directly.

============================================================
15. SAFETY
============================================================

Never encourage:

- illegal pesticide use
- dangerous chemical mixing
- poisoning
- unsafe pesticide handling
- deliberate environmental contamination
- unsafe animal medication
- harmful chemical exposure

For pesticides and chemicals:

Always emphasize following the product label and applicable
local regulations.

For serious poisoning or human exposure, recommend urgent
professional medical assistance.

============================================================
16. COMMUNICATION
============================================================

Use clear English unless the user asks for another language.

You may use simple agricultural terminology.

Avoid unnecessarily complicated academic language.

When the farmer appears inexperienced, explain terms.

When the user is technically knowledgeable, provide more detail.

Do not repeatedly say:

"As an AI..."

Instead, focus on helping the farmer.

Do not make every answer excessively long.

============================================================
17. PERSONALIZATION
============================================================

The farmer's name and account role may be provided separately.

Use the farmer's name naturally when appropriate, but do not
overuse it.

If farm information is provided in the conversation, remember it
within the supplied conversation history and use it to personalize
later answers.

============================================================
18. HONESTY
============================================================

Never fabricate:

- weather
- crop prices
- agricultural statistics
- government policies
- pesticide registrations
- disease diagnoses
- laboratory results
- soil test results
- expert consultations

When current or local information is required and unavailable,
say so clearly.

============================================================
19. FINAL GOAL
============================================================

Your goal is not simply to answer questions.

Your goal is to help the farmer make better agricultural decisions
by combining:

- agricultural knowledge
- practical reasoning
- risk awareness
- sustainable farming
- economic thinking
- clear communication
- context-aware recommendations

PROMPT;


/* =========================================================
   BUILD CONVERSATION HISTORY
========================================================= */

$history =
    $data['history']
    ?? [];


if (!is_array($history)) {
    $history = [];
}


/*
|--------------------------------------------------------------------------
| Keep only recent messages.
| This prevents unnecessarily huge requests.
|--------------------------------------------------------------------------
*/

$history =
    array_slice(
        $history,
        -20
    );


/* =========================================================
   BUILD INPUT
========================================================= */

$input = [];


/*
|--------------------------------------------------------------------------
| System/developer instruction
|--------------------------------------------------------------------------
*/

$input[] = [
    'role' => 'developer',
    'content' => $systemInstructions
];


/*
|--------------------------------------------------------------------------
| Farmer information
|--------------------------------------------------------------------------
*/

$input[] = [
    'role' => 'developer',
    'content' =>
        "Current user information:\n" .
        "Name: " . $userName . "\n" .
        "Role: " . $userRole . "\n" .
        "User ID: " . (string)$userId
];


/*
|--------------------------------------------------------------------------
| Conversation history
|--------------------------------------------------------------------------
*/

foreach ($history as $item) {

    if (!is_array($item)) {
        continue;
    }

    $role =
        $item['role']
        ?? '';

    $content =
        trim(
            (string)(
                $item['content']
                ?? ''
            )
        );


    if (
        $content === '' ||
        !in_array(
            $role,
            ['user', 'assistant', 'ai'],
            true
        )
    ) {
        continue;
    }


    if ($role === 'ai') {
        $role = 'assistant';
    }


    /*
     * Prevent excessive history entries.
     */
    if (mb_strlen($content) > 8000) {
        $content =
            mb_substr(
                $content,
                0,
                8000
            );
    }


    $input[] = [
        'role' => $role,
        'content' => $content
    ];
}


/*
|--------------------------------------------------------------------------
| Current user message
|--------------------------------------------------------------------------
*/

$input[] = [
    'role' => 'user',
    'content' => $message
];


/* =========================================================
   OPENAI REQUEST
========================================================= */

$requestBody = [

    'model' => $model,

    'input' => $input,

    /*
     * Gives the model room to reason through difficult
     * agricultural questions.
     */
    'reasoning' => [
        'effort' => 'medium'
    ],

    /*
     * Keep responses useful without becoming excessively long.
     */
    'max_output_tokens' => 2500

];


/* =========================================================
   CURL
========================================================= */

$ch =
    curl_init(
        'https://api.openai.com/v1/responses'
    );


curl_setopt_array(
    $ch,
    [

        CURLOPT_POST => true,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [

            'Content-Type: application/json',

            'Authorization: Bearer ' .
                $apiKey

        ],

        CURLOPT_POSTFIELDS =>
            json_encode(
                $requestBody,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),

        CURLOPT_CONNECTTIMEOUT => 20,

        CURLOPT_TIMEOUT => 120,

        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_SSL_VERIFYHOST => 2

    ]
);


/* =========================================================
   EXECUTE
========================================================= */

$response =
    curl_exec($ch);


$curlError =
    curl_error($ch);


$httpCode =
    (int)curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/* =========================================================
   CURL ERROR
========================================================= */

if ($response === false) {

    error_log(
        'Agro AI CURL error: ' .
        $curlError
    );

    http_response_code(502);

    echo json_encode([
        'success' => false,
        'error' =>
            'The AI service could not be reached. Please check the server internet connection.'
    ]);

    exit;
}


/* =========================================================
   DECODE OPENAI RESPONSE
========================================================= */

$responseData =
    json_decode(
        $response,
        true
    );


if (!is_array($responseData)) {

    error_log(
        'Agro AI invalid API response: ' .
        $response
    );

    http_response_code(502);

    echo json_encode([
        'success' => false,
        'error' =>
            'The AI service returned an invalid response.'
    ]);

    exit;
}


/* =========================================================
   OPENAI ERROR
========================================================= */

if ($httpCode < 200 || $httpCode >= 300) {

    $apiError =
        $responseData['error']['message']
        ?? 'Unknown OpenAI API error.';


    error_log(
        'Agro AI OpenAI error [' .
        $httpCode .
        ']: ' .
        $apiError
    );


    http_response_code(
        $httpCode >= 400 &&
        $httpCode < 600
            ? $httpCode
            : 502
    );


    echo json_encode([
        'success' => false,
        'error' =>
            'The farming AI service returned an error. Please check your AI API configuration.'
    ]);

    exit;
}


/* =========================================================
   EXTRACT OUTPUT TEXT
========================================================= */

$reply =
    '';


/*
|--------------------------------------------------------------------------
| Responses API commonly exposes output_text.
|--------------------------------------------------------------------------
*/

if (
    isset(
        $responseData['output_text']
    ) &&
    is_string(
        $responseData['output_text']
    )
) {

    $reply =
        trim(
            $responseData['output_text']
        );
}


/*
|--------------------------------------------------------------------------
| Fallback parser.
|--------------------------------------------------------------------------
*/

if ($reply === '') {

    $output =
        $responseData['output']
        ?? [];


    if (is_array($output)) {

        foreach ($output as $item) {

            if (
                !is_array($item)
            ) {
                continue;
            }


            $content =
                $item['content']
                ?? [];


            if (
                !is_array($content)
            ) {
                continue;
            }


            foreach ($content as $part) {

                if (
                    !is_array($part)
                ) {
                    continue;
                }


                if (
                    isset(
                        $part['text']
                    ) &&
                    is_string(
                        $part['text']
                    )
                ) {

                    $reply .=
                        $part['text'];
                }
            }
        }
    }


    $reply =
        trim(
            $reply
        );
}


/* =========================================================
   EMPTY RESPONSE
========================================================= */

if ($reply === '') {

    error_log(
        'Agro AI empty response: ' .
        $response
    );

    http_response_code(502);

    echo json_encode([
        'success' => false,
        'error' =>
            'The AI returned an empty answer. Please try again.'
    ]);

    exit;
}


/* =========================================================
   SUCCESS
========================================================= */

echo json_encode(

    [

        'success' => true,

        'reply' => $reply,

        'conversation_id' =>
            $conversationId

    ],

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

);

exit;