<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;

class DocuSignController extends Controller
{
    public function create()
    {
        return Inertia::render('Authorization/Create');
    }

    public function send(Request $request)
    {
         $name  = $request->signer_name;
        $email = $request->signer_email;


        // JWT Assertion for OAuth
        $baseUrl = 'https://demo.docusign.net/restapi';
        $integratorKey = env('DOCUSIGN_INTEGRATION_KEY');
        $userId        = env('DOCUSIGN_USER_ID');
        $privateKey    = storage_path('app/docusign_private.key');
        $jwtPayload = [
            'iss'   => $integratorKey,
            'sub'   => $userId,
            'aud'   => 'account-d.docusign.com',
            'exp'   => time() + 3600,
            'scope' => 'signature impersonation',
        ];
        $jwt = JWT::encode($jwtPayload, file_get_contents($privateKey), 'RS256');
        $client = new Client ([
            'verify' => false, // Disable SSL verification for testing (not recommended in production)
        ]);
        // 1) Get access token
        $authRes = $client->post("https://account-d.docusign.com/oauth/token", [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);


        $accessToken = json_decode($authRes->getBody(), true)['access_token'];
        if (!$accessToken) {
            return redirect()->back()->withErrors(['error' => 'Failed to obtain access token']);
        }

        // 2) Build envelope definition
        $pdfBase64 = base64_encode(file_get_contents(storage_path('app/myfile.pdf')));
        $envelopeDef = [
            'emailSubject'       => 'Please sign your Authorization Form',
            'envelopeIdStamping' => 'true',            // stamps envelope ID on PDF  //documents: $pdfBase64,
            'documents' => [[
                'documentBase64' => base64_encode(
                    file_get_contents(storage_path('app/myfile.pdf'))
                ),
                'name'           => 'Authorization.pdf',
                'fileExtension'  => 'pdf',
                'documentId'     => '1',
            ]],
            'recipients' => [
                'signers' => [[
                    'email'        => $email,
                    'name'         => $name,
                    'recipientId'  => '1',
                    'routingOrder' => '1',]],],
                    // 'clientUserId' => '1000',           // embedded signing

                    // 'tabs' => [
                    //     // Prefill their name
                    //     'textTabs' => [[
                    //         'tabLabel'   => 'SignerName',
                    //         'value'      => $name,
                    //         'documentId' => '1',
                    //         'pageNumber' => '1',
                    //         'xPosition'  => '100',
                    //         'yPosition'  => '150',
                    //     ]],
                    //     // Signature box at anchor
                    //     'signHereTabs' => [[
                    //         'anchorString'  => '/sign_here/',
                    //         'anchorUnits'   => 'pixels',
                    //         'anchorXOffset' => '0',
                    //         'anchorYOffset' => '0',
            'status' => 'sent', // 'created' for draft, 'sent' to send immediately


        ];

        $accountId = env('DOCUSIGN_ACCOUNT_ID');
        if (!$accountId) {
            return redirect()->back()->withErrors(['error' => 'DocuSign account ID not configured']);
        }
        $baseUrl   = 'https://demo.docusign.net/restapi';
        // 3) Create envelope
        $envRes = $client->post("$baseUrl/v2.1/accounts/$accountId/envelopes", [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type'  => 'application/json',
            ],
            'json' => $envelopeDef,
        ]);
        $envelopeId = json_decode($envRes->getBody(), true)['envelopeId'];

        return response()-> json([
            'success' => true,
            'envelopeId' => $envelopeId,
        ]);
    }}

        // // 4) Create embedded recipient view (signing URL)
        // $viewReq = [
        //     'returnUrl'            => route('dashboard.authorization.callback', ['envelope_id' => $envelopeId]),
        //     'authenticationMethod' => 'none',
        //     'email'                => $email,
        //     'userName'             => $name,
        //     'clientUserId'         => '1000',
        // ];
        // $viewRes = $client->post(
        //     "$baseUrl/v2.1/accounts/$accountId/envelopes/$envelopeId/views/recipient",
        //     ['headers'=>['Authorization'=>"Bearer $accessToken"], 'json'=>$viewReq]
        // );
        // $signingUrl = json_decode($viewRes->getBody(), true)['url'];

        // // 5) Redirect user into DocuSign signing ceremony
        // return redirect()->away($signingUrl);

//new new new new new new new new new new new new new new new new new new new




//everything below this line is for embedded signing

//         // after $envelopeId is set
//         $viewDef = [
//         'returnUrl'            => route('dashboard.authorization.callback', ['envelope_id' => $envelopeId]),
//         'authenticationMethod' => 'none',
//         'email'                => $email,
//         'userName'             => $name,
//         'clientUserId'         => '1000',
//         ];
//         $viewRes = $client->post(
//         "{$baseUrl}/v2.1/accounts/{$accountId}/envelopes/{$envelopeId}/views/recipient",
//         ['json' => $viewDef]
//         );
//         $signingUrl = json_decode($viewRes->getBody(), true)['url'];

//         return Inertia::location($signingUrl);
//     }

//     public function callback(Request $request)
//     {
//         $envelopeId = $request->query('envelope_id');
//         $accountId  = env('DOCUSIGN_ACCOUNT_ID');

//         $client = new Client([
//             'headers' => ['Authorization'=>"Bearer ". $this->getAccessToken()],
//             'verify'  => false, // Disable SSL verification for testing (not recommended in production)
//         ]);
//         $baseUrl = 'https://demo.docusign.net/restapi';

//         // 6) Fetch the completed, stamped PDF
//         $res = $client->get("$baseUrl/v2.1/accounts/$accountId/envelopes/$envelopeId/documents/combined", [
//             'stream' => true,
//         ]);

//         // 7) Stream it back to browser
//         return response($res->getBody(), 200)
//             ->header('Content-Type', 'application/pdf')
//             ->header('Content-Disposition', "inline; filename=\"signed-{$envelopeId}.pdf\"");
//     }

//     protected function getAccessToken()
//     {
//         // (Extract your JWT/OAuth code into here for reuse in callback)
//         $integratorKey = env('DOCUSIGN_INTEGRATOR_KEY');
//         $userId        = env('DOCUSIGN_USER_ID');
//         $privateKey    = storage_path('app/docusign_private.key');
//         $jwtPayload = [
//             'iss'   => $integratorKey,
//             'sub'   => $userId,
//             'aud'   => 'account-d.docusign.com',
//             'exp'   => time() + 3600,
//             'scope' => 'signature impersonation',
//         ];
//         $jwt = JWT::encode($jwtPayload, file_get_contents($privateKey), 'RS256');
//         $client = new Client([
//             'verify' => false, // Disable SSL verification for testing (not recommended in production)
//         ]);
//         $res = $client->post("https://account-d.docusign.com/oauth/token", [
//             'form_params' => [
//                 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
//                 'assertion'  => $jwt,
//             ],
//         ]);
//         return json_decode($res->getBody(), true)['access_token'];
//     }
// }
