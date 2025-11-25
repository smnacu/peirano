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
}
?>