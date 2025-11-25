<?php
class OutlookSync
{
    private $tenantId = '';
    private $clientId = '';
    private $clientSecret = '';
    private $userId = '';

    private $accessToken = null;

    private function getAccessToken()
    {
        if ($this->accessToken)
            return $this->accessToken;

        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";
        $data = [
            'client_id' => $this->clientId,
            'scope' => 'https://graph.microsoft.com/.default',
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['access_token'])) {
            $this->accessToken = $json['access_token'];
            return $this->accessToken;
        } else {
            return null;
        }
    }

    public function createEvent($subject, $startTime, $endTime, $description)
    {
        $token = $this->getAccessToken();
        if (!$token)
            return null;

        $url = "https://graph.microsoft.com/v1.0/users/{$this->userId}/events";

        $event = [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $description
            ],
            'start' => [
                'dateTime' => $startTime,
                'timeZone' => 'Argentina Standard Time'
            ],
            'end' => [
                'dateTime' => $endTime,
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
            return null;
        }
    }

    public function checkAvailability($startTime, $endTime)
    {
        $token = $this->getAccessToken();
        if (!$token)
            return false;

        $url = "https://graph.microsoft.com/v1.0/users/{$this->userId}/calendarView?startDateTime={$startTime}&endDateTime={$endTime}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['value']) && count($json['value']) > 0) {
            return false;
        }

        return true;
    }

    public function getDailyAvailability($date)
    {
        $token = $this->getAccessToken();
        if (!$token)
            return ['error' => 'No se pudo obtener el token de acceso a Microsoft Graph.'];

        // Definir rango del día completo (00:00 a 23:59) en hora local
        $startDateTime = $date . 'T00:00:00';
        $endDateTime = $date . 'T23:59:59';

        $url = "https://graph.microsoft.com/v1.0/users/{$this->userId}/calendarView?startDateTime={$startDateTime}&endDateTime={$endDateTime}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json",
            "Prefer: outlook.timezone=\"Argentina Standard Time\"" // Importante: Pedir horas en local
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode >= 400) {
            return ['error' => 'Error de API Microsoft: ' . ($json['error']['message'] ?? 'Desconocido')];
        }

        $events = $json['value'] ?? [];

        // Generar slots de 07:00 a 18:00
        $slots = [];
        for ($hour = 7; $hour <= 18; $hour++) {
            $time = sprintf('%02d:00', $hour);
            $slotStart = strtotime("$date $time");
            $slotEnd = $slotStart + 3600; // 1 hora de duración

            $isBusy = false;
            foreach ($events as $event) {
                // Las fechas ya vienen en hora local gracias al header Prefer
                $eventStart = strtotime($event['start']['dateTime']);
                $eventEnd = strtotime($event['end']['dateTime']);

                // Verificar superposición
                // Un slot está ocupado si el evento empieza antes de que termine el slot
                // Y el evento termina después de que empiece el slot
                if ($eventStart < $slotEnd && $eventEnd > $slotStart) {
                    $isBusy = true;
                    break;
                }
            }

            $slots[] = [
                'time' => $time,
                'available' => !$isBusy
            ];
        }

        return $slots;
    }
}
?>