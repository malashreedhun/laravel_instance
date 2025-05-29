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
        $name  = $request->input('signer_name');
        $email = $request->input('signer_email');

        //
        // 1) JWT Assertion for OAuth
        //
        $integratorKey   = env('DOCUSIGN_INTEGRATION_KEY');
        $userId          = env('DOCUSIGN_USER_ID');
        $privateKeyPath  = storage_path('app/docusign_private.key');
        $jwtPayload = [
            'iss'   => $integratorKey,
            'sub'   => $userId,
            'aud'   => 'account-d.docusign.com',
            'exp'   => time() + 3600,
            'scope' => 'signature impersonation',
        ];
        $jwt = JWT::encode($jwtPayload, file_get_contents($privateKeyPath), 'RS256');

        $client = new Client([
            'verify' => false, // disable SSL verification in dev
        ]);

        $authRes = $client->post('https://account-d.docusign.com/oauth/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        $accessToken = data_get(json_decode($authRes->getBody(), true), 'access_token');
        if (!$accessToken) {
            return redirect()->back()->withErrors(['error' => 'Failed to obtain access token']);
        }

        //
        // 2) Build envelope definition
        //
        $pdfContent = file_get_contents(storage_path('app/myfile.pdf'));
        $envelopeDef = [
            'emailSubject'       => 'Please sign your Authorization Form',
            'emailBlurb'         => 'Please review and sign the attached form.',
            'envelopeIdStamping' => true,
            'documents' => [
                [
                    'documentBase64' => base64_encode($pdfContent),
                    'name'           => 'Authorization.pdf',
                    'fileExtension'  => 'pdf',
                    'documentId'     => '1',
                ],
            ],
            'recipients' => [
                'signers' => [
                    [
                        'email'        => $email,
                        'name'         => $name,
                        'recipientId'  => '1',
                        'routingOrder' => '1',
                        'clientUserId' => '1000', // embedded signing
                    ],
                ],
            ],
            'status' => 'sent',
        ];

        $accountId = env('DOCUSIGN_ACCOUNT_ID');
        if (!$accountId) {
            return redirect()->back()->withErrors(['error' => 'DocuSign account ID not configured']);
        }

        $baseUrl = 'https://demo.docusign.net/restapi';

        //
        // 3) Create the envelope
        //
        $envRes = $client->post("$baseUrl/v2.1/accounts/$accountId/envelopes", [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type'  => 'application/json',
            ],
            'json' => $envelopeDef,
        ]);

        $envelopeId = data_get(json_decode($envRes->getBody(), true), 'envelopeId');

        //
        // 4) Create the embedded signing view
        //
        $viewRequest = [
            'returnUrl'            => route('dashboard.authorization.callback', ['envelope_id' => $envelopeId]),
            'authenticationMethod' => 'none',
            'email'                => $email,
            'userName'             => $name,
            'clientUserId'         => '1000',
        ];

        try {
            $viewRes = $client->post(
                "$baseUrl/v2.1/accounts/$accountId/envelopes/$envelopeId/views/recipient",
                [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $viewRequest,
                ]
            );

            $signingUrl = data_get(json_decode($viewRes->getBody(), true), 'url');
        } catch (\Exception $e) {
            // Log or inspect the error
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }

        //
        // 5) Return the envelope ID + signing URL
        //
        return response()->json([
            'success'    => true,
            'envelopeId' => $envelopeId,
            'signingUrl' => $signingUrl,
        ]);
    }

    public function callback(Request $request)
    {
        $envelopeId = $request->query('envelope_id');
        $accountId  = env('DOCUSIGN_ACCOUNT_ID');
        $accessToken = $this->getAccessToken();

        $client = new Client([
            'headers' => ['Authorization' => "Bearer $accessToken"],
            'verify'  => false,
        ]);

        $baseUrl = 'https://demo.docusign.net/restapi';

        //
        // 6) Fetch the completed, combined PDF
        //
        $res = $client->get("$baseUrl/v2.1/accounts/$accountId/envelopes/$envelopeId/documents/combined", [
            'stream' => true,
        ]);

        //
        // 7) Stream it back inline
        //
        return response($res->getBody(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"signed-{$envelopeId}.pdf\"");
    }

    protected function getAccessToken()
    {
        $integratorKey   = env('DOCUSIGN_INTEGRATION_KEY');
        $userId          = env('DOCUSIGN_USER_ID');
        $privateKeyPath  = storage_path('app/docusign_private.key');
        $jwtPayload = [
            'iss'   => $integratorKey,
            'sub'   => $userId,
            'aud'   => 'account-d.docusign.com',
            'exp'   => time() + 3600,
            'scope' => 'signature impersonation',
        ];
        $jwt = JWT::encode($jwtPayload, file_get_contents($privateKeyPath), 'RS256');

        $client = new Client([
            'verify' => false,
        ]);
        $res = $client->post('https://account-d.docusign.com/oauth/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        return data_get(json_decode($res->getBody(), true), 'access_token');
    }
}
