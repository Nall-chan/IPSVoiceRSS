<?

class TTSVoiceRSS extends IPSModule
{

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Apikey', '');
        $this->RegisterPropertyString('Language', 'de-de');
        $this->RegisterPropertyString('Codec', 'MP3');
        $this->RegisterPropertyString('Sample', '8khz_8bit_mono');
        IPS_SetInfo($this->InstanceID, 'Register at http://www.voicerss.org/');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

################## PUBLIC
    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     */

    public function GenerateFile(string $Text, string $Filename)
    {

        $Format = $this->ReadPropertyString('Sample');
        $Codec = $this->ReadPropertyString('Codec');
        $Language = $this->ReadPropertyString('Language');
        return $this->GenerateFileEx($Text, $Filename, $Format, $Codec, $Language);
    }

    public function GenerateFileEx(string $Text, string $Filename, string $Format, string $Codec, string $Language)
    {
        if ((strpos($Filename, '.' . strtolower($Codec))) === false)
            $Filename .='.' . strtolower($Codec);
        return $this->LoadTTSFile($Text, $Filename, 0, $Format, $Codec, $Language, false);
    }

    public function GetDataContent(string $Text)
    {
        $Format = $this->ReadPropertyString('Sample');
        $Codec = $this->ReadPropertyString('Codec');
        $Language = $this->ReadPropertyString('Language');
        return $this->GetDataContentEx($Text, $Format, $Codec, $Language);
    }

    public function GetDataContentEx(string $Text, string $Format, string $Codec, string $Language)
    {
        return $this->LoadTTSFile($Text, '', 0, $Format, $Codec, $Language, true);
    }

    public function GenerateMediaObject(string $Text, integer $MediaID)
    {

        $Format = $this->ReadPropertyString('Sample');
        $Codec = $this->ReadPropertyString('Codec');
        $Language = $this->ReadPropertyString('Language');
        return $this->GenerateMediaObjectEx($Text, $MediaID, $Format, $Codec, $Language);
    }

    public function GenerateMediaObjectEx(string $Text, integer $MediaID, string $Format, string $Codec, string $Language)
    {

        if ($MediaID == 0)
            $MediaID = @IPS_GetObjectIDByIdent('Voice', $this->InstanceID);
        if ($MediaID > 0)
        {
            if (IPS_MediaExists($MediaID) === false)
                throw new Exception('MediaObject not exists.');
            if (IPS_GetMedia($MediaID)['MediaType'] <> 2)
                throw new Exception('Wrong MediaType');
        }
        if ($MediaID === false)
        {
            $MediaID = IPS_CreateMedia(2);
            IPS_SetMediaCached($MediaID);
            IPS_SetName($MediaID, 'Voice');
            IPS_SetIdent($MediaID, 'Voice');
        }

        $Filename = 'media' . DIRECTORY_SEPARATOR . $MediaID . '.' . strtolower($Codec);

        $raw = $this->LoadTTSFile($Text, '', 0, $Format, $Codec, $Language, true);

        IPS_SetMediaFile($MediaID, $Filename, False);
        IPS_SetMediaContent($MediaID, base64_encode($raw));
        IPS_SetInfo($MediaID, $Text);
        return $MediaID;
    }

################## PRIVATE    

    protected function LoadTTSFile(string $Text, string $Filename, integer $Speed, string $Format, string $Codec, string $Language, boolean $raw)
    {
        if (trim($Text) == '')
            throw new Exception('Text is empty');

        $ApiData['key'] = $this->ReadPropertyString('Apikey');
        $ApiData['src'] = $Text;
        $ApiData['hl'] = $Language;
        $ApiData['r'] = $Speed;
        $ApiData['c'] = $Codec;
        $ApiData['f'] = $Format;

        $header[] = "Accept: */*";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: close";
        $header[] = "Accept-Charset: UTF-8";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.voicerss.org/");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ApiData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 3000);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);

        $result = curl_exec($ch);
        curl_close($ch);
        if ($result === false)
            throw new Exception("Error on get VoiceData");

        If ($raw)
            return $result;

        try
        {
            $fh = fopen($Filename, 'w');
            fwrite($fh, $result);
        } catch (Exception $exc)
        {
            
        } finally
        {
            fclose($fh);
        }
        if (isset($exc))
            throw new Exception($exc);

        return true;
    }

}

?>