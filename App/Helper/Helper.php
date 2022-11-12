<?php

namespace App\Helper;

class Helper
{
    public static function returnJSONError($error, $params = [])
    {
        $response = ['success' => false, 'message' => $error];
        if (!empty($params['action'])) {
            $response['action'] = $params['action'];
        }


        return $response;
    }

    public static function returnJSONWarning($error, $params = [])
    {
        $response = ['success' => true, 'warning' => true, 'message' => $error];
        if (!empty($params['action'])) {
            $response['action'] = $params['action'];
        }

        return $response;
    }

    public static function convertIDNAToUnicode($unicode)
    {
        if (extension_loaded('intl')) {
            // we try the UTS #46 standard first
            // as this is the new default, see https://sourceforge.net/p/icu/mailman/message/32980778/
            // We know that this fails for some Google domains violating the standard
            // see https://github.com/pi-hole/AdminLTE/issues/1223
            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                // We have to use the option IDNA_NONTRANSITIONAL_TO_ASCII here
                // to ensure sparkasse-gießen.de is not converted into
                // sparkass-giessen.de but into xn--sparkasse-gieen-2ib.de
                // as mandated by the UTS #46 standard
                $unicode = idn_to_utf8($unicode, IDNA_NONTRANSITIONAL_TO_ASCII);
            } elseif (defined('INTL_IDNA_VARIANT_2003')) {
                // If conversion failed, try with the (deprecated!) IDNA 2003 variant
                // We have to check for its existence as support of this variant is
                // scheduled for removal with PHP 8.0
                // see https://wiki.php.net/rfc/deprecate-and-remove-intl_idna_variant_2003
                $unicode = idn_to_utf8($unicode, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
            }
        }

        return $unicode;
    }

    public static function convertUnicodeToIDNA($IDNA)
    {
        if (extension_loaded('intl')) {
            // Be prepared that this may fail and see our comments about convertIDNAToUnicode()
            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                $IDNA = idn_to_ascii($IDNA, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
            } elseif (defined('INTL_IDNA_VARIANT_2003')) {
                $IDNA = idn_to_ascii($IDNA, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
            }
        }

        return $IDNA;
    }

    public static function validDomain($domain_name, &$message = null)
    {
        // special handling of the root zone `.`
        if ($domain_name === '.') {
            return true;
        }

        if (!preg_match('/^((-|_)*[a-z\\d]((-|_)*[a-z\\d])*(-|_)*)(\\.(-|_)*([a-z\\d]((-|_)*[a-z\\d])*))*$/i', $domain_name)) {
            if ($message !== null) {
                $message = 'it contains invalid characters';
            }

            return false;
        }
        if (!preg_match('/^.{1,253}$/', $domain_name)) {
            if ($message !== null) {
                $message = 'its length is invalid';
            }

            return false;
        }
        if (!preg_match('/^[^\\.]{1,63}(\\.[^\\.]{1,63})*$/', $domain_name)) {
            if ($message !== null) {
                $message = 'at least one label is of invalid length';
            }

            return false;
        }

        // everything is okay
        return true;
    }

    public static function validIP($ip)
    {
        if (preg_match('/[.:0]/', $ip) && !preg_match('/[1-9a-f]/', $ip)) {
            // Test if address contains either `:` or `0` but not 1-9 or a-f
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * Find subclasses for a given Abstract
     * @param $parent
     * @return array
     */
    public static function getSubclassesOf($parent)
    {
        $result = [];
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            if (is_subclass_of($class, $parent)) {
                $result[] = $class;
            }
        }

        return $result;
    }

    public static function formatLine($line)
    {
        $txt = preg_replace('/ dnsmasq\\[[0-9]*\\]/', '', htmlspecialchars($line));

        if (strpos($line, 'blacklisted') || strpos($line, 'gravity blocked')) {
            $txt = '<b class="log-red">' . $txt . '</b>';
        } elseif (strpos($line, 'query[A') || strpos($line, 'query[DHCP')) {
            $txt = '<b>' . $txt . '</b>';
        } else {
            $txt = '<span class="text-muted">' . $txt . '</span>';
        }

        return $txt;
    }


    public static function pidOf($process)
    {
        return shell_exec(sprintf('pidof %s', $process));
    }

    public static function formatByteUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' kB';
        } elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public static function validCIDRIP($address)
    {
        // This validation strategy has been taken from ../js/groups-common.js
        $isIPv6 = strpos($address, ':') !== false;
        if ($isIPv6) {
            // One IPv6 element is 16bit: 0000 - FFFF
            $v6elem = '[0-9A-Fa-f]{1,4}';
            // dnsmasq allows arbitrary prefix-length since https://thekelleys.org.uk/gitweb/?p=dnsmasq.git;a=commit;h=35f93081dc9a52e64ac3b7196ad1f5c1106f8932
            $v6cidr = '([1-9]|[1-9][0-9]|1[01][0-9]|12[0-8])';
            $validator = "/^(((?:{$v6elem}))((?::{$v6elem}))*::((?:{$v6elem}))((?::{$v6elem}))*|((?:{$v6elem}))((?::{$v6elem})){7})\\/{$v6cidr}$/";

            return preg_match($validator, $address);
        }
        // One IPv4 element is 8bit: 0 - 256
        $v4elem = '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|0)';
        // dnsmasq allows arbitrary prefix-length
        $allowedv4cidr = '(([1-9]|[12][0-9]|3[0-2]))';
        $validator = "/^{$v4elem}\\.{$v4elem}\\.{$v4elem}\\.{$v4elem}\\/{$allowedv4cidr}$/";

        return preg_match($validator, $address);
    }

    public static function validDomainWildcard($domain_name)
    {
        // Skip this checks for the root zone `.`
        if ($domain_name === '.') {
            return true;
        }
        // There has to be either no or at most one "*" at the beginning of a line
        $validChars = preg_match('/^((\\*\\.)?[_a-z\\d](-*[_a-z\\d])*)(\\.([_a-z\\d](-*[a-z\\d])*))*(\\.([_a-z\\d])*)*$/i', $domain_name);
        $lengthCheck = preg_match('/^.{1,253}$/', $domain_name);
        $labelLengthCheck = preg_match('/^[^\\.]{1,63}(\\.[^\\.]{1,63})*$/', $domain_name);

        return $validChars && $lengthCheck && $labelLengthCheck; // length of each label
    }

    /**
     * Convert a second thing in to a readable time
     * @param $time
     * @return string
     */
    public static function secondsToTime($time)
    {
        $seconds = round($time);
        if ($seconds < 60) {
            return sprintf('%ds', $seconds);
        }
        if ($seconds < 3600) {
            return sprintf('%dm %ds', $seconds / 60, $seconds % 60);
        }
        if ($seconds < 86400) {
            return sprintf('%dh %dm %ds', $seconds / 3600 % 24, $seconds / 60 % 60, $seconds % 60);
        }

        return sprintf('%dd %dh %dm %ds', $seconds / 86400, $seconds / 3600 % 24, $seconds / 60 % 60, $seconds % 60);
    }
}
