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
}
