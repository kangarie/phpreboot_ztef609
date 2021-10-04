<?php

require 'simple_html_dom.php';

use GlobalFunction as Func;

/**
 * ZteF609
 *
 * CATATAN FENTING DEK:
 * - Library ini dibuat tidak untuk kejahatan ataupun kegiatan yang merugikan orang lain,
 *   apalagi untuk usil ke teman atau sanak saudara, itu dosa dek. Mending langsung gelut aja.
 *
 * @author walangkaji (https://github.com/walangkaji)
 */

class Constants
{
    const TEMPLATE           = '/template.gch';
    const PARAM              = '/getpage.gch?pid=1002&nextpage=';
    const DEVICE_INFORMATION = self::PARAM . 'status_dev_info_t.gch';
    const VOIP_STATUS        = self::PARAM . 'status_voip_4less_t.gch';
    const REBOOT             = self::PARAM . 'manager_dev_conf_t.gch';
    const WAN_CONNECTION     = self::PARAM . 'IPv46_status_wan2_if_t.gch';
    const PON_INFORMATION    = self::PARAM . 'pon_status_link_info_t.gch';
    const MOBILE_NETWORK     = self::PARAM . 'status_mobnet_info_t.gch';
}

class GlobalFunction
{
    /**
     * Untuk cari string diantara string
     *
     * @param string $content contentnya
     * @param string $start   awalan
     * @param string $end     akhiran
     */
    public static function getBetween($content, $start, $end)
    {
        $r = explode($start, $content);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }

        return '';
    }

    public static function find($content, $start, $end)
    {
        if (preg_match('/' . $start . '(.*?)' . $end .'/', $content, $match) == 1) {
            return $match[1];
        }
        return '';
    }

    public static function toFixed($number, $decimals)
    {
        return number_format($number, $decimals, '.', '');
    }
}

class ZteApi
{
    public function __construct($ipModem, $username, $password, $debug = false, $proxy = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->proxy    = $proxy;
        $this->debug    = $debug ? true : false;
        $this->modemUrl = "http://$ipModem";

        $this->status   = new Status($this);
    }

    /**
     * Fungsi untuk login
     *
     * @return bool
     */
    public function login()
    {
        $cekLogin = $this->cekLogin();

        if ($cekLogin) {
            $this->debug(__FUNCTION__, 'Login session masih aktif.');

            return true;
        }

        $get  = $this->request($this->modemUrl);
        $rand = rand(10000000, 99999999);

        $postdata = [
            'action'         => 'login',
            'Username'       => $this->username,
            'Password'       => hash('sha256', $this->password . $rand),
            'Frm_Logintoken' => Func::find($get, 'Frm_Logintoken", "', '"'),
            'UserRandomNum'  => $rand,
        ];

        $options = [
            'method'   => 'post',
            'postdata' => $postdata,
        ];

        $this->request($this->modemUrl, $options);
        $cekLogin = $this->cekLogin();

        if ($cekLogin) {
            $this->debug(__FUNCTION__, 'Berhasil login dengan user ' . $this->username);
        } else {
            $this->debug(__FUNCTION__, 'Gagal login dengan user ' . $this->username);
        }

        return $cekLogin;
    }

    /**
     * Fungsi untuk reboot modem
     *
     * @return bool
     */
    public function reboot()
    {
        $get = $this->request($this->modemUrl . Constants::REBOOT);

        $postdata = [
            'IF_ACTION'      => 'devrestart',
            'IF_ERRORSTR'    => 'SUCC',
            'IF_ERRORPARAM'  => 'SUCC',
            'IF_ERRORTYPE'   => -1,
            'flag'           => 1,
            '_SESSION_TOKEN' => Func::find($get, 'session_token = "', '";'),
        ];

        $options = [
            'method'   => 'post',
            'postdata' => $postdata,
        ];

        $request = $this->request($this->modemUrl . Constants::REBOOT, $options);
        $cek     = Func::find($request, "flag','", "'");

        if ($cek == 1) {
            $this->debug(__FUNCTION__, 'Berhasil Reboot modem.');

            return true;
        }

        $this->debug(__FUNCTION__, 'Gagal Reboot modem.');

        return false;
    }

    /**
     * Fungsi untuk cek login
     *
     * @return bool
     */
    private function cekLogin()
    {
        $url      = $this->modemUrl . Constants::TEMPLATE;
        $response = $this->request($url);

        if ($this->httpCode != 200) {
            return false;
        }

        return true;
    }

    /**
     * Untuk debug proses
     *
     * @param mixed $function
     * @param mixed $text
     */
    private function debug($function, $text = '')
    {
        $space = 10 - strlen($function);
        $space = ($space < 0) ? 0 : $space;

        if ($this->debug) {
            echo '[' . date('h:i:s A') . "]: $function" . str_repeat(' ', $space);
            echo(empty($text) ? '' : ': ' . $text) . PHP_EOL;
        }
    }

    /**
     * Curl request
     *
     * @param string $url     url request
     * @param array  $options options yang akan digunakan
     *                        array  header    untuk setting headernya
     *                        string useragent untuk set useragent
     *                        string method    'post', 'put', 'delete'
     */
    public function request($url, $options = [])
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.87 Safari/537.36';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }

        if (!empty($options)) {
            if (isset($options['header'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $options['header']);
            } elseif (isset($options['useragent'])) {
                curl_setopt($ch, CURLOPT_USERAGENT, $options['useragent']);
            } elseif (isset($options['method'])) {
                if (strtolower($options['method']) == 'post') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['postdata']));
                } elseif (strtolower($options['method']) == 'delete') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                } elseif (strtolower($options['method']) == 'put') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['postdata']));
                }
            }
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $html           = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $html;
    }
}

class Status extends ZteApi
{
    public function __construct($parent)
    {
        $this->zte              = $parent;
        $this->NetworkInterface = new NetworkInterface($this);
        $this->UserInterface    = new UserInterface($this);
    }

    /**
     * Get device information
     *
     * @return object
     */
    public function deviceInformation()
    {
        $request = $this->zte->request($this->zte->modemUrl . Constants::DEVICE_INFORMATION);
        $dom     = str_get_html($request);

        $data = [];
        foreach ($dom->find('table#TABLE_DEV tr') as $key) {
            $cari        = $key->find('td');
            $keys        = strtolower(str_replace(' ', '_', $cari[0]->plaintext));
            $data[$keys] = ltrim(rtrim(html_entity_decode($cari[1]->plaintext)));

            if ($keys == 'pon_serial_number') {
                $data[$keys] = Func::find($dom, 'var sn = "', '";');
            }
        }

        return json_decode(json_encode($data));
    }

    /**
     * Get VoIP Status
     *
     * @return object
     */
    public function voIpStatus()
    {
        $request = $this->zte->request($this->zte->modemUrl . Constants::VOIP_STATUS);
        $dom     = str_get_html($request);

        foreach ($dom->find('table#TestContent td[class=tdright]') as $key) {
            $status[] = $key->innertext;
        }

        $data = [
            'phone1'           => $status[0],
            'register_status1' => $status[1],
            'phone2'           => $status[2],
            'register_status2' => $status[3],
        ];

        return json_decode(json_encode($data));
    }
}

class NetworkInterface extends Status
{
    public function __construct($parent)
    {
        $this->zte = $parent->zte;
    }

    /**
     * Get WAN Connection information
     *
     * @return object
     */
    public function wanConnection()
    {
        $request = $this->zte->request($this->zte->modemUrl . Constants::WAN_CONNECTION);
        $dom     = str_get_html($request);

        $data = [];

        foreach ($dom->find('table#TestContent0 tr') as $key) {
            $cari  = $key->find('td');
            $keys  = strtolower(str_replace(' ', '_', $cari[0]->plaintext));


            if ($keys == 'disconnect_reason') {
                $value = $cari[1]->plaintext;
            } else {
                $value = html_entity_decode($key->find('td.tdright input', 0)->attr['value']);
            }

            $data[$keys] = $value;
        }

        return json_decode(json_encode($data));
    }

    /**
     * 3G/4G WAN Connection
     */
    public function wanConnection3Gor4G()
    {
        return json_decode(json_encode(['error' => 'Under Maintenance']));
    }

    /**
     * 4in6 Tunnel Connection
     */
    public function tunnelConnection4in6()
    {
        return json_decode(json_encode(['error' => 'Under Maintenance']));
    }

    /**
     * 6in4 Tunnel Connection
     */
    public function tunnelConnection6in4()
    {
        return json_decode(json_encode(['error' => 'Under Maintenance']));
    }

    /**
     * PON information
     */
    public function ponInformation()
    {
        $request   = $this->zte->request($this->zte->modemUrl . Constants::PON_INFORMATION);
        $dom       = str_get_html($request);
        $regStatus = intval(Func::find($request, 'var RegStatus = "', '"'));

        switch ($regStatus) {
            case 1:
                $GponRegStatus = 'Initial State(o1)';
                break;
            case 2:
                $GponRegStatus = 'Standby State(o2)';
                break;
            case 3:
                $GponRegStatus = 'Serial Number State(o3)';
                break;
            case 4:
                $GponRegStatus = 'Ranging State(o4)';
                break;
            case 5:
                $GponRegStatus = 'Operation State(o5)';
                break;
            case 6:
                $GponRegStatus = 'POPUP State(o6)';
                break;
            case 7:
                $GponRegStatus = 'Emergency Stop State(o7)';
                break;
            default:
                $GponRegStatus = 'Unknown State';
                break;
        }

        $data    = [];
        $rxPower = Func::find($request, 'RxPower = "', '"') / 10000;
        $txPower = Func::find($request, 'TxPower = "', '"') / 10000;

        $data['gpon_state']                          = $GponRegStatus;
        $data['optical_module_input_power']          = Func::toFixed($rxPower, 1) . ' dBm';
        $data['optical_module_output_power']         = Func::toFixed($txPower, 1) . ' dBm';
        $data['optical_module_supply_voltage']       = $dom->find('td[id=Frm_Volt]', 0)->plaintext . ' uV';
        $data['optical_transmitter_bias_current']    = $dom->find('td[id=Frm_Current]', 0)->plaintext . ' uA';
        $data['optical_temperature_of_optical_mode'] = $dom->find('td[id=Frm_Temp]', 0)->plaintext . ' C';

        return json_decode(json_encode($data));
    }

    /**
     * Mobile Network
     */
    public function mobileNetwork()
    {
        $request   = $this->zte->request($this->zte->modemUrl . Constants::MOBILE_NETWORK);
        $dom       = str_get_html($request);

        $data = [];
        $data['service_provider'] = null;
        $data['network_mode']     = null;
        $data['signal_strength']  = count($dom->find('div.divbox'));
        $data['imei']             = null;
        $data['dongle_type']      = null;

        return json_decode(json_encode($data));
    }
}

class UserInterface extends Status
{
    public function __construct($parent)
    {
        $this->zte = $parent->zte;
    }

    /**
     * Get WAN Connection information
     *
     * @return object
     */
    public function wlan()
    {
        $request = $this->zte->request($this->zte->modemUrl . '/getpage.gch?pid=1002&nextpage=status_wlanm_info1_t.gch');
        preg_match_all('/>Transfer_meaning\((.*?)\);/', $request, $match);
        $result = str_replace("'", '', $match[1]);

        foreach ($result as $key) {
            $pecah = explode(',', $key);
            $dat[$pecah[0]] = stripcslashes($pecah[1]);
        }

        $map = [];
        for ($i=0; $i <= 3 ; $i++) {
            $authType = $this->getAuthenticationType(
                $dat["BeaconType$i"],
                $dat["WEPAuthMode$i"],
                $dat["WPAAuthMode$i"],
                $dat["11iAuthMode$i"]
            );

            $encryptionType = $this->getEncryptionType(
                $dat["BeaconType$i"],
                $dat["WPAEncryptType$i"],
                $dat["11iEncryptType$i"]
            );

            $map["wlan_$i"] = [
                'enable'                   => boolval($dat["Enable$i"]),
                'ssid_name'                => $dat["ESSID$i"],
                'authentication_type'      => $authType,
                'encryption_type'          => $encryptionType,
                'mac_address'              => $dat["Bssid$i"],
                'packets_received'         => $dat["TotalPacketsReceived$i"],
                'packets_sent'             => $dat["TotalPacketsSent$i"],
                'bytes_received'           => $dat["TotalBytesReceived$i"],
                'bytes_sent'               => $dat["TotalBytesSent$i"],
                'errors_received'          => $dat["ErrorsReceived$i"],
                'errors_sent'              => $dat["ErrorsSent$i"],
                'discard_packets_received' => $dat["DiscardPacketsReceived$i"],
                'discard_packets_sent'     => $dat["DiscardPacketsSent$i"],
                'wlan_ssid'                => $dat["WLAN_SSID$i"],
                'radio_status'             => boolval($dat["RadioStatus$i"]),
                'wep_auth_mode'            => $dat["WEPAuthMode$i"],
                'beacon_type'              => $dat["BeaconType$i"],
                'wpa_encrypt_type'         => $dat["WPAEncryptType$i"],
                'wpa_auth_mode'            => $dat["WPAAuthMode$i"],
                '11i_auth_mode'            => $dat["11iAuthMode$i"],
                '11i_encrypt_type'         => $dat["11iEncryptType$i"],
                'wds_mode'                 => $dat["WdsMode$i"],
                'channel_in_used'          => $dat["ChannelInUsed$i"],
                'mac_address'              => $dat["Bssid$i"],
                'real_rf'                  => $dat["RealRF$i"],
            ];
        }

        return json_decode(json_encode($map));
    }

    /**
     * Get Ethernet information
     *
     * @return object
     */
    public function ethernet()
    {
        $request = $this->zte->request($this->zte->modemUrl . '/getpage.gch?pid=1002&nextpage=pon_status_lan_info_t.gch');
        $dom     = str_get_html($request);
        $data    = [];

        foreach ($dom->find('table#TestContent tr') as $key => $val) {
            $cari          = $val->find('td');
            $keys          = strtolower(str_replace([' ', '/'], '_', rtrim($cari[0]->plaintext)));
            $data[$keys][] = html_entity_decode($cari[1]->plaintext);
        }

        $map = [];
        for ($i=0; $i <= 3 ; $i++) {
            $received = explode('/', $data['packets_received_bytes_received'][$i]);
            $sent     = explode('/', $data['packets_sent_bytes_sent'][$i]);

            $packets_received = $received[0];
            $byte_received    = $received[1];
            $packets_sent     = $sent[0];
            $byte_sent        = $sent[1];

            $map["lan_$i"] = [
                'ethernet_port'    => $data['ethernet_port'][$i],
                'status'           => $data['status'][$i],
                'speed'            => $data['speed'][$i],
                'mode'             => $data['mode'][$i],
                'packets_received' => $packets_received,
                'packets_sent'     => $packets_sent,
                'byte_received'    => $byte_received,
                'byte_sent'        => $byte_sent,
            ];
        }

        return json_decode(json_encode($map));
    }

    /**
     * Get USB information
     *
     * @return object
     */
    public function usb()
    {
        $request = $this->zte->request($this->zte->modemUrl . '/getpage.gch?pid=1002&nextpage=status_usb_info_t.gch');
        $dom     = str_get_html($request);
        $data    = [];

        foreach ($dom->find('table#TestContent tr') as $key => $val) {
            $cari        = $val->find('td');
            $keys        = strtolower(str_replace([' ', '/'], '_', rtrim($cari[0]->plaintext)));
            $data[$keys] = html_entity_decode($cari[1]->plaintext);
        }

        return json_decode(json_encode($data));
    }

    /**
     * Get Authentication Type
     *
     * @param  string $beaconType  emboh pikir keri
     * @param  string $WEPAuthMode emboh pikir keri
     * @param  string $WPAAuthMode emboh pikir keri
     * @param  string $AuthMode11i emboh pikir keri
     */
    private function getAuthenticationType($beaconType, $WEPAuthMode, $WPAAuthMode, $AuthMode11i)
    {
        if ($beaconType == "None" || ($beaconType == "Basic" && $WEPAuthMode == "None")) {
            return 'Open System';
        } elseif ($beaconType == "Basic" && $WEPAuthMode == "SharedAuthentication") {
            return 'Shared Key';
        } elseif ($beaconType == "WPA" && $WPAAuthMode == "PSKAuthentication") {
            return 'WPA-PSK';
        } elseif ($beaconType == "11i" && $AuthMode11i == "PSKAuthentication") {
            return 'WPA2-PSK';
        } elseif ($beaconType == "WPAand11i" && $WPAAuthMode == "PSKAuthentication" && $AuthMode11i == "PSKAuthentication") {
            return 'WPA/WPA2-PSK';
        } elseif ($beaconType == "WPA" && $WPAAuthMode == "EAPAuthentication") {
            return 'WPA-EAP';
        } elseif ($beaconType == "11i" && $AuthMode11i == "EAPAuthentication") {
            return 'WPA2-EAP';
        } elseif ($beaconType == "WPAand11i" && $WPAAuthMode == "EAPAuthentication" && $AuthMode11i == "EAPAuthentication") {
            return 'WPA/WPA2-EAP';
        }
    }

    /**
     * Get Encryption Type
     *
     * @param  string $beaconType     emboh ra dong
     * @param  string $WPAEncryptType emboh ra dong
     * @param  string $EncryptType11i emboh ra dong
     */
    private function getEncryptionType($beaconType, $WPAEncryptType, $EncryptType11i)
    {
        if ($beaconType == "None") {
            return 'None';
        } elseif ($beaconType == "Basic") {
            return 'WEP';
        } elseif (($beaconType == "WPA" && $WPAEncryptType == "TKIPEncryption") ||
            ($beaconType == "11i" && $EncryptType11i == "TKIPEncryption") ||
            ($beaconType == "WPAand11i" && $WPAEncryptType == "TKIPEncryption")) {
            return 'TKIP';
        } elseif (($beaconType == "WPA" && $WPAEncryptType == "AESEncryption") ||
            ($beaconType == "11i" && $EncryptType11i == "AESEncryption") ||
            ($beaconType == "WPAand11i" && $WPAEncryptType == "AESEncryption")) {
            return 'AES';
        } elseif (($beaconType == "WPA" && $WPAEncryptType == "TKIPandAESEncryption") ||
            ($beaconType == "11i" && $EncryptType11i == "TKIPandAESEncryption") ||
            ($beaconType == "WPAand11i" && $WPAEncryptType == "TKIPandAESEncryption")) {
            return 'TKIP+AES';
        }
    }
}
