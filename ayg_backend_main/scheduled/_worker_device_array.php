<?php

use App\Delivery\Helpers\EncryptionHelper;

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


// $x=$deviceArray = 'eyJpc0FuZHJvaWQiOjAsImlzT25XaWZpIjoiMSIsImRldmljZUlkIjoiQTQwREM5QzctN0JBOC00Qjc0LTlCMjItRTIwMzkxQ0E5M0FFIiwicHVzaE5vdGlmaWNhdGlvbklkIjoiMCIsImdlb0xhdGl0dWRlIjoiMC4wIiwiZGV2aWNlVHlwZSI6ImlQaG9uZSIsImRldmljZU1vZGVsIjoiU2ltdWxhdG9yIiwiaXNJb3MiOjEsImFwcFZlcnNpb24iOiIyLjAuMSIsImRldmljZU9TIjoiMTQuNCIsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOiIwIiwiY291bnRyeSI6IlVTIiwiZ2VvTG9uZ2l0dWRlIjoiMC4wIiwidGltZXpvbmVGcm9tVVRDSW5TZWNvbmRzIjowfQ==';

$deviceArray = 'eyJpc0FuZHJvaWQiOjAsImFwcFZlcnNpb24iOiIyLjAuMSIsImRldmljZUlkIjoiM0U2ODVDODktOUU0OS00MjgxLTkzNTItRkU0NkU4REY1OEJBIiwicHVzaE5vdGlmaWNhdGlvbklkIjoiMCIsImlzSW9zIjoxLCJkZXZpY2VUeXBlIjoiaVBob25lIiwiaXNQdXNoTm90aWZpY2F0aW9uRW5hYmxlZCI6IjAiLCJkZXZpY2VPUyI6IjEyLjUuMiIsImlzT25XaWZpIjoiMSIsImNvdW50cnkiOiJQTCIsInRpbWV6b25lRnJvbVVUQ0luU2Vjb25kcyI6NzIwMCwiZ2VvTG9uZ2l0dWRlIjoiMC4wIiwiZ2VvTGF0aXR1ZGUiOiIwLjAiLCJkZXZpY2VNb2RlbCI6ImlQaG9uZSA1cyJ9';
$deviceArrayDecoded = decodeDeviceArray($deviceArray);
var_dump([2,$deviceArrayDecoded]);

// password
// +kkWtV7o2LDOR0VwAZYhbEXLfdLYmWjXHuQppfU7Ius=:1/tgMQXGJ1qMNQOaJSoD+w==

$password = EncryptionHelper::encryptStringInMotion('deliveryfirst@runner');

var_dump('eyJpc0FuZHJvaWQiOjAsImFwcFZlcnNpb24iOiIyLjAuMSIsImRldmljZUlkIjoiM0U2ODVDODktOUU0OS00MjgxLTkzNTItRkU0NkU4REY1OEJBIiwicHVzaE5vdGlmaWNhdGlvbklkIjoiMCIsImlzSW9zIjoxLCJkZXZpY2VUeXBlIjoiaVBob25lIiwiaXNQdXNoTm90aWZpY2F0aW9uRW5hYmxlZCI6IjAiLCJkZXZpY2VPUyI6IjEyLjUuMiIsImlzT25XaWZpIjoiMSIsImNvdW50cnkiOiJQTCIsInRpbWV6b25lRnJvbVVUQ0luU2Vjb25kcyI6NzIwMCwiZ2VvTG9uZ2l0dWRlIjoiMC4wIiwiZ2VvTGF0aXR1ZGUiOiIwLjAiLCJkZXZpY2VNb2RlbCI6ImlQaG9uZSA1cyJ9');
var_dump($password);


function encodeDeviceArray($deviceArray)
{

        $array = (base64_encode(json_encode($deviceArray)));
    return $array;
}
?>
