<?php
// graph.php
require_once 'config.php';

class GraphHandler
{
    private $accessToken = null;

    private function getAccessToken()
    {
        if ($this->accessToken)
            return $this->accessToken;

        $url = "https://login.microsoftonline.com/" . TENANT_ID . "/oauth2/v2.0/token";
        $data = [
            'client_id' => CLIENT_ID,
            'scope' => 'https://graph.microsoft.com/.default',
            'client_secret' => CLIENT_SECRET,
            'grant_type' => 'client_credentials'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Error obteniendo token de acceso: $response");
        }

        $json = json_decode($response, true);
        $this->accessToken = $json['access_token'];
        return $this->accessToken;
    }

    public function createEvent($appointmentData)
    {
        try {
            $token = $this->getAccessToken();

            $url = "https://graph.microsoft.com/v1.0/users/" . CALENDAR_USER_ID . "/events";

            $subject = "PROVEEDOR - " . $appointmentData['vehicle_type'] . " - " . $appointmentData['company_name'];

            $description = "
                <b>Proveedor:</b> {$appointmentData['company_name']}<br>
                <b>CUIT:</b> {$appointmentData['cuit']}<br>
                <b>Vehículo:</b> {$appointmentData['vehicle_type']}<br>
                <b>Bultos/Pallets:</b> {$appointmentData['quantity']}<br>
                <b>Autoelevador:</b> " . ($appointmentData['needs_forklift'] ? 'SÍ' : 'NO') . "<br>
                <b>Observaciones:</b> {$appointmentData['observations']}
            ";

            $event = [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $description
                ],
                'start' => [
                    'dateTime' => $appointmentData['start_time'],
                    'timeZone' => 'Argentina Standard Time'
                ],
                'end' => [
                    'dateTime' => $appointmentData['end_time'],
                    'timeZone' => 'Argentina Standard Time'
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $token",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $json = json_decode($response, true);
                return $json['id'];
            } else {
                throw new Exception("Error creando evento en Graph: $response");
            }
        } catch (Exception $e) {
            // Log error but don't stop execution if possible, or rethrow
            // For this requirement, we want to handle it gracefully
            return null;
        }
    }
}
?>