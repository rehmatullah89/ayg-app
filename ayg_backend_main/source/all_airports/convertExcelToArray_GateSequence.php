<?php



// Just Terminal + Concourse as the key
$fileArrayGateMap = [



    "SAN" => [
        '2' => './airport_specific/SAN/SAN-data/SAN-GateMap-2.csv',
        '1' => './airport_specific/SAN/SAN-data/SAN-GateMap-1.csv',
        'Admin' => './airport_specific/SAN/SAN-data/SAN-Admin.csv',
        'PMC' => './airport_specific/SAN/SAN-data/SAN-PMC.csv',
    ],




    "EWR" => [

        'AA1' => './airport_specific/EWR/EWR-data/EWR-GateMap-A1.csv',
        'AA2' => './airport_specific/EWR/EWR-data/EWR-GateMap-A2.csv',
        'AA3' => './airport_specific/EWR/EWR-data/EWR-GateMap-A3.csv',
        'APre' => './airport_specific/EWR/EWR-data/EWR-GateMap-APre.csv',
        'BB1' => './airport_specific/EWR/EWR-data/EWR-GateMap-B1.csv',
        'BB2' => './airport_specific/EWR/EWR-data/EWR-GateMap-B2.csv',
        'BB3' => './airport_specific/EWR/EWR-data/EWR-GateMap-B3.csv',
        'BPre' => './airport_specific/EWR/EWR-data/EWR-GateMap-BPre.csv',
        'CPre' => './airport_specific/EWR/EWR-data/EWR-GateMap-CPre.csv',
        'P3Car' => './airport_specific/EWR/EWR-data/EWR-GateMap-P3.csv',
        'P4Daily' => './airport_specific/EWR/EWR-data/EWR-GateMap-P4.csv',
    ],
    "BOS" => [
        'C' => './airport_specific/BOS/BOS-data/BOS-GateMap-C.csv',
        'E' => './airport_specific/BOS/BOS-data/BOS-GateMap-E.csv',
        'A' => './airport_specific/BOS/BOS-data/BOS-GateMap-A.csv',
        'B' => './airport_specific/BOS/BOS-data/BOS-GateMap-B.csv',
        'LOC' => './airport_specific/BOS/BOS-data/BOS-GateMap-LOC.csv',
    ],
    "JFK" => [
        '4' => './airport_specific/JFK/JFK-data/JFK-GateMap-4.csv',
        '5' => './airport_specific/JFK/JFK-data/JFK-GateMap-5.csv',
        '8' => './airport_specific/JFK/JFK-data/JFK-GateMap-8.csv',
    ],

    "LGA" => [
        'BA' => './airport_specific/LGA/LGA-data/LGA-GateMap-B-A.csv',
        'BB' => './airport_specific/LGA/LGA-data/LGA-GateMap-B-B.csv',
        'BD' => './airport_specific/LGA/LGA-data/LGA-GateMap-B-D.csv',
        'BPre' => './airport_specific/LGA/LGA-data/LGA-GateMap-Pre.csv',
        'BPG' => './airport_specific/LGA/LGA-data/LGA-GateMap-PG.csv',
    ],
    "MSP" => [
        'A' => './airport_specific/MSP/MSP-data/MSP-GateMap-A.csv',
        'B' => './airport_specific/MSP/MSP-data/MSP-GateMap-B.csv',
        'C' => './airport_specific/MSP/MSP-data/MSP-GateMap-C.csv',
        'D' => './airport_specific/MSP/MSP-data/MSP-GateMap-D.csv',
        'E' => './airport_specific/MSP/MSP-data/MSP-GateMap-E.csv',
        'F' => './airport_specific/MSP/MSP-data/MSP-GateMap-F.csv',
        'G' => './airport_specific/MSP/MSP-data/MSP-GateMap-G.csv',
        'k-IH' => './airport_specific/MSP/MSP-data/MSP-GateMap-IH.csv',
        'i-Mezz' => './airport_specific/MSP/MSP-data/MSP-GateMap-Mezzanine.csv',
        'h-AM' => './airport_specific/MSP/MSP-data/MSP-GateMap-AirportMall.csv',
        'j-MT' => './airport_specific/MSP/MSP-data/MSP-GateMap-MT.csv',
        'PO' => './airport_specific/MSP/MSP-data/MSP-GateMap-PO.csv',
        'SR' => './airport_specific/MSP/MSP-data/MSP-GateMap-SR.csv',
        'TC' => './airport_specific/MSP/MSP-data/MSP-GateMap-TC.csv',
    ],
    "PDX" => [
        'B' => './airport_specific/PDX/PDX-data/PDX-GateMap-B.csv',
        'C' => './airport_specific/PDX/PDX-data/PDX-GateMap-C.csv',
        'E' => './airport_specific/PDX/PDX-data/PDX-GateMap-E.csv',
        'D' => './airport_specific/PDX/PDX-data/PDX-GateMap-D.csv',
        'Pre' => './airport_specific/PDX/PDX-data/PDX-GateMap-Pre.csv',
    ],


    "DEN" => [
        'B' => './airport_specific/DEN/DEN-data/DEN-TB.csv',
        'C' => './airport_specific/DEN/DEN-data/DEN-TC.csv',
        'A' => './airport_specific/DEN/DEN-data/DEN-TA.csv',
        'Term' => './airport_specific/DEN/DEN-data/DEN-Admin.csv',
    ],


/*
    "MDW" => [
        'TA' => './airport_specific/MDW/MDW-data/MDW-GateMap-A.csv',
        'TB' => './airport_specific/MDW/MDW-data/MDW-GateMap-B.csv',
        'TC' => './airport_specific/MDW/MDW-data/MDW-GateMap-C.csv',
        'Td-CM' => './airport_specific/MDW/MDW-data/MDW-GateMap-CM.csv',
        'Te-Main' => './airport_specific/MDW/MDW-data/MDW-GateMap-MT.csv',
        'Tf-Bag' => './airport_specific/MDW/MDW-data/MDW-GateMap-Bag.csv',
        'Tg-EE' => './airport_specific/MDW/MDW-data/MDW-GateMap-EE.csv',
    ],
*/

    "SEA" => [
        'A' => './airport_specific/SEA/SEA-data/SEA-GateMap-A.csv',
        'B' => './airport_specific/SEA/SEA-data/SEA-GateMap-B.csv',
        'C' => './airport_specific/SEA/SEA-data/SEA-GateMap-C.csv',
        'D' => './airport_specific/SEA/SEA-data/SEA-GateMap-D.csv',
        'N' => './airport_specific/SEA/SEA-data/SEA-GateMap-N.csv',
        'S' => './airport_specific/SEA/SEA-data/SEA-GateMap-S.csv',
        't-Mez' => './airport_specific/SEA/SEA-data/SEA-GateMap-Mez.csv',
        'u-Tix' => './airport_specific/SEA/SEA-data/SEA-GateMap-Tix.csv',
        'v-Bag' => './airport_specific/SEA/SEA-data/SEA-GateMap-Bag.csv',
    ],



    "DFW" => [
        'A' => './airport_specific/DFW/DFW-data/DFW-GateMap-A.csv',
        'B' => './airport_specific/DFW/DFW-data/DFW-GateMap-B.csv',
        'C' => './airport_specific/DFW/DFW-data/DFW-GateMap-C.csv',
        'D' => './airport_specific/DFW/DFW-data/DFW-GateMap-D.csv',
        'E' => './airport_specific/DFW/DFW-data/DFW-GateMap-E.csv',
    ],



    "PHL" => [
        'AE' => './airport_specific/PHL/PHL-data/PHL-GateMap-AE.csv',
        'AW' => './airport_specific/PHL/PHL-data/PHL-GateMap-AW.csv',
        'B' => './airport_specific/PHL/PHL-data/PHL-GateMap-B.csv',
        'h-bag' => './airport_specific/PHL/PHL-data/PHL-GateMap-Bag.csv',
        'C' => './airport_specific/PHL/PHL-data/PHL-GateMap-C.csv',
        'D' => './airport_specific/PHL/PHL-data/PHL-GateMap-D.csv',
        'E' => './airport_specific/PHL/PHL-data/PHL-GateMap-E.csv',
        'F' => './airport_specific/PHL/PHL-data/PHL-GateMap-F.csv',
        'j-ldg' => './airport_specific/PHL/PHL-data/PHL-GateMap-Ldg.csv',
        'g-offices' => './airport_specific/PHL/PHL-data/PHL-GateMap-Offices.csv',
        'k-pu' => './airport_specific/PHL/PHL-data/PHL-GateMap-PU.csv',
        'i-tck' => './airport_specific/PHL/PHL-data/PHL-GateMap-Ticket.csv',
    ],


    "MDW" => [
        'A' => './airport_specific/MDW/MDW-data/MDW-GateMap-A.csv',
        'B' => './airport_specific/MDW/MDW-data/MDW-GateMap-B.csv',
        'C' => './airport_specific/MDW/MDW-data/MDW-GateMap-C.csv',
        'd-CM' => './airport_specific/MDW/MDW-data/MDW-GateMap-CM.csv',
        'e-Main' => './airport_specific/MDW/MDW-data/MDW-GateMap-MT.csv',
        'f-Bag' => './airport_specific/MDW/MDW-data/MDW-GateMap-Bag.csv',
        'g-EE' => './airport_specific/MDW/MDW-data/MDW-GateMap-EE.csv',
    ],



    "SLC" => [
        'A' => './airport_specific/SLC/SLC-data/SLC-GateMap-A.csv',
        'B' => './airport_specific/SLC/SLC-data/SLC-GateMap-B.csv',
        'Term' => './airport_specific/SLC/SLC-data/SLC-GateMap-Terminal.csv',
    ],


    "TPA" => [
        'A' => './airport_specific/TPA/TPA-data/TPA-GateMap-A.csv',
        'C' => './airport_specific/TPA/TPA-data/TPA-GateMap-C.csv',
        'n-Car' => './airport_specific/TPA/TPA-data/TPA-GateMap-Car.csv',
        'E' => './airport_specific/TPA/TPA-data/TPA-GateMap-E.csv',
        'F' => './airport_specific/TPA/TPA-data/TPA-GateMap-F.csv',
        'MT' => './airport_specific/TPA/TPA-data/TPA-GateMap-MT.csv',
    ],


    "BWI" => [
        'A' => './airport_specific/BWI/BWI-data/BWI-GateMap-A.csv',
        'B' => './airport_specific/BWI/BWI-data/BWI-GateMap-B.csv',
        'C' => './airport_specific/BWI/BWI-data/BWI-GateMap-C.csv',
        'D' => './airport_specific/BWI/BWI-data/BWI-GateMap-D.csv',
        'E' => './airport_specific/BWI/BWI-data/BWI-GateMap-E.csv',
        'TLL' => './airport_specific/BWI/BWI-data/BWI-GateMap-TLL.csv',
        'TML' => './airport_specific/BWI/BWI-data/BWI-GateMap-TML.csv',
        'TUL' => './airport_specific/BWI/BWI-data/BWI-GateMap-TUL.csv',
    ],


    "LAX" => [
        'TB100Gates' => './airport_specific/LAX/LAX-data/LAX-GateMap-TB-100Gates.csv',
        'TB200Gates' => './airport_specific/LAX/LAX-data/LAX-GateMap-TB-200Gates.csv',
        'TBLounges' => './airport_specific/LAX/LAX-data/LAX-GateMap-TB-Lounges.csv',
        'TBTBPre' => './airport_specific/LAX/LAX-data/LAX-GateMap-TBPre.csv',
        '1T1Pre' => './airport_specific/LAX/LAX-data/LAX-GateMap-T1Pre.csv',
        '1T1Gates' => './airport_specific/LAX/LAX-data/LAX-GateMap-T1Gates.csv',
    ],

    "CLE" => [
        'A' => './airport_specific/CLE/CLE-data/CLE-GateMap-A.csv',
        'B' => './airport_specific/CLE/CLE-data/CLE-GateMap-B.csv',
        'C' => './airport_specific/CLE/CLE-data/CLE-GateMap-C.csv',
        'd-offices' => './airport_specific/CLE/CLE-data/CLE-GateMap-d-offices.csv',
        'e-bag' => './airport_specific/CLE/CLE-data/CLE-GateMap-e-baggage.csv',
        'f-tsa' => './airport_specific/CLE/CLE-data/CLE-GateMap-f-TSA.csv',
        'g-ticketing' => './airport_specific/CLE/CLE-data/CLE-GateMap-g-Ticketing.csv',
        'MT' => './airport_specific/CLE/CLE-data/CLE-GateMap-MT.csv',
    ],

];


// index - terminal concorse
$fileArrayGateMapDirections = $fileArrayGateMap;


// @todo - we can do it without cords (empty cells)
$fileArrayGateCords = [
   "SAN" => './airport_specific/SAN/SAN-data/SAN-GateCords.csv',
    "BOS" => './airport_specific/BOS/BOS-data/BOS-GateCords.csv',
    "EWR" => './airport_specific/EWR/EWR-data/EWR-GateCords.csv',
    "JFK" => './airport_specific/JFK/JFK-data/JFK-GateCords.csv',
    "LGA" => './airport_specific/LGA/LGA-data/LGA-GateCords.csv',
    "MSP" => './airport_specific/MSP/MSP-data/MSP-GateCords.csv',
    "PDX" => './airport_specific/PDX/PDX-data/PDX-GateCords.csv',
    "DEN" => './airport_specific/DEN/DEN-data/DEN-GateCords.csv',
   // "MDW" => './airport_specific/MDW/MDW-data/MDW-GateCords.csv',
    "SEA" => './airport_specific/SEA/SEA-data/SEA-GateCords.csv',
    "DFW" => './airport_specific/DFW/DFW-data/DFW-GateCords.csv',
    "PHL" => './airport_specific/PHL/PHL-data/PHL-GateCords.csv',
    "MDW" => './airport_specific/MDW/MDW-data/MDW-GateCords.csv',
    "SLC" => './airport_specific/SLC/SLC-data/SLC-GateCords.csv',
    "TPA" => './airport_specific/TPA/TPA-data/TPA-GateCords.csv',
    "BWI" => './airport_specific/BWI/BWI-data/BWI-GateCords.csv',
    "LAX" => './airport_specific/LAX/LAX-data/LAX-GateCords.csv',
    "CLE" => './airport_specific/CLE/CLE-data/CLE-GateCords.csv',

];


// Terminal Mapping
$fileArrayT2TMap = [
    "SAN" => './airport_specific/SAN/SAN-data/SAN-TerminalConnection.csv',
    "BOS" => './airport_specific/BOS/BOS-data/BOS-TerminalConnection.csv',
    "EWR" => './airport_specific/EWR/EWR-data/EWR-TerminalConnection.csv',
    "JFK" => './airport_specific/JFK/JFK-data/JFK-TerminalConnection.csv',
    "LGA" => './airport_specific/LGA/LGA-data/LGA-TerminalConnection.csv',
    "MSP" => './airport_specific/MSP/MSP-data/MSP-TerminalConnection.csv',
    "PDX" => './airport_specific/PDX/PDX-data/PDX-TerminalConnection.csv',
    "DEN" => './airport_specific/DEN/DEN-data/DEN-TerminalConnection.csv',
   // "MDW" => './airport_specific/MDW/MDW-data/MDW-TerminalConnection.csv',
    "SEA" => './airport_specific/SEA/SEA-data/SEA-TerminalConnection.csv',
    "DFW" => './airport_specific/DFW/DFW-data/DFW-TerminalConnection.csv',
    "PHL" => './airport_specific/PHL/PHL-data/PHL-TerminalConnection.csv',
    "MDW" => './airport_specific/MDW/MDW-data/MDW-TerminalConnection.csv',
    "SLC" => './airport_specific/SLC/SLC-data/SLC-TerminalConnection.csv',
    "TPA" => './airport_specific/TPA/TPA-data/TPA-TerminalConnection.csv',
    "BWI" => './airport_specific/BWI/BWI-data/BWI-TerminalConnection.csv',
    "LAX" => './airport_specific/LAX/LAX-data/LAX-TerminalConnection.csv',
    "CLE" => './airport_specific/CLE/CLE-data/CLE-TerminalConnection.csv',
];

foreach ($fileArrayGateMap as $airportIataCode => $gateMapFiles) {

    foreach ($gateMapFiles as $gate => $fileName) {

        $jsonGateMap[$airportIataCode][$gate] = convertGateMapToArray(array_map('str_getcsv', file($fileName)),
            array_map('str_getcsv', file($fileArrayGateMapDirections[$airportIataCode][$gate])));
    }

    $jsonGateCords[$airportIataCode] = getGateCords(array_map('str_getcsv',
        file($fileArrayGateCords[$airportIataCode])));

    $jsonT2TMap[$airportIataCode] = convertT2TMapToArray(array_map('str_getcsv',
        file($fileArrayT2TMap[$airportIataCode])));
}


// Write to Config file
$fp = fopen('../lib/gatemaps.inc.php', 'w');
fwrite($fp, '<' . '?php');
fwrite($fp, "\n\n\t" . '$' . 'jsonGateMap = ' . "json_decode('" . json_encode($jsonGateMap) . "', true);");
fwrite($fp, "\n\n\t" . '$' . 'jsonT2TMap = ' . "json_decode('" . json_encode($jsonT2TMap) . "', true);" . "\n\n");
fwrite($fp, "\n\n\t" . '$' . 'jsonGateCords = ' . "json_decode('" . json_encode($jsonGateCords) . "', true);" . "\n\n");
fwrite($fp, '?' . '>');
fclose($fp);

echo("generated!");

function getGateCords($fileArray)
{

    $gateCords = array();

    // Skip the Header row and create key arrays
    // $objectKeys = array_map('trim', explode(",", array_shift($fileArray)));
    $objectKeys = array_map('trim', array_shift($fileArray));

    foreach ($fileArray as $valueString) {

        // $valueArray = explode(",",$valueString);
        $valueArray = $valueString;

        $valueArray[0] = trim($valueArray[0]);
        $valueArray[1] = trim($valueArray[1]);
        $valueArray[2] = trim($valueArray[2]);

        $gateCords[$valueArray[0] . $valueArray[1] . $valueArray[2]]['x'] = trim($valueArray[3]);
        $gateCords[$valueArray[0] . $valueArray[1] . $valueArray[2]]['y'] = trim($valueArray[4]);
    }

    return $gateCords;
}

function convertT2TMapToArray($fileArray)
{

    $t2tMap = array();

    // Skip the Header row and create key arrays
    // $objectKeys = array_map('trim', explode(",", array_shift($fileArray)));
    $objectKeys = array_map('trim', array_shift($fileArray));


    foreach ($fileArray as $valueString) {

        // $valueArray = explode(",",$valueString);
        $valueArray = $valueString;

        // From Terminal
        $valueArray[0] = trim($valueArray[0]);

        // From Concourse
        $valueArray[1] = trim($valueArray[1]);

        // To Terminal
        $valueArray[2] = trim($valueArray[2]);

        // To Concourse
        $valueArray[3] = trim($valueArray[3]);

        // Skip first 4 columns
        for ($i = 4; $i < count($valueArray); $i++) {

            $valueArray[$i] = trim($valueArray[$i]);

            if (empty($valueArray[$i])) {

                $valueArray[$i] = "";
            }

            $t2tMap[$valueArray[0] . $valueArray[1] . '-' . $valueArray[2] . $valueArray[3]][$objectKeys[$i]] = $valueArray[$i];
        }
    }

    return $t2tMap;
}

function convertGateMapToArray($fileArrayGateMap, $fileArrayGateMapDirections)
{

    // Skip the Header row and create key arrays
    // $objectKeys = array_map('trim', explode(",", array_shift($fileArrayGateMap)));
    $objectKeys = array_map('trim', array_shift($fileArrayGateMap));

    // $objectKeysBak = array_map('trim', explode(",", array_shift($fileArrayGateMapDirections)));
    $objectKeysBak = array_map('trim', array_shift($fileArrayGateMapDirections));

    // gate to gate mappings
    $sequenceArray = array();

    // Only distance
    $distanceArray = array();

    // Only directional paths
    $directionsArray = array();

    // Both distance and directional as per Dijkstra graph requirement
    $directionsWithDistanceArray = array();

    foreach ($fileArrayGateMap as $j => $valueArray) {

        // $valueArray = explode(",",$valueString);
        $valueArray[0] = trim($valueArray[0]);

        // $directionValueArray = explode(",",$fileArrayGateMapDirections[$j]);
        $directionValueArray = $fileArrayGateMapDirections[$j];
        $directionValueArray[0] = trim($directionValueArray[0]);


        for ($i = 1; $i < count($valueArray); $i++) {

            $valueArray[$i] = trim($valueArray[$i]);

            $valueArrayKey0 = $valueArray[0];

            $valueDistance = $valueArray[$i];
            $valueDirectionCue = "";

            // If it has a ; that means there is a Direction Cue
            if (strpos($valueDistance, ";")) {

                list($valueDistance, $valueDirectionCue) = explode(";", $valueArray[$i]);
            }

            $objectKeysArrayKeyi = $objectKeys[$i];

            $directionValueArray[$i] = trim($directionValueArray[$i]);

            // Directions Array
            if (!empty($directionValueArray[$i])) {

                // Add its distance to the list
                $directionsArray["text"]["$valueArrayKey0" . '-' . "$objectKeysArrayKeyi"] = trim($directionValueArray[$i]);
                //$directionsArray["text"]["$objectKeysArrayKeyi" . '-' . "$valueArrayKey0"] = trim($directionValueArray[$i]);
                $directionsArray["cue"]["$valueArrayKey0" . '-' . "$objectKeysArrayKeyi"] = trim($valueDirectionCue);
            }

            if (!empty($valueArray[$i])) {

                // Add Gate number to the list
                if (!isset($sequenceArray["$valueArrayKey0"]) || !in_array($objectKeysArrayKeyi,
                        $sequenceArray["$valueArrayKey0"])
                ) {

                    $sequenceArray["$valueArrayKey0"][] = $objectKeysArrayKeyi;
                }

                if (!isset($sequenceArray["$objectKeysArrayKeyi"]) || !in_array($valueArrayKey0,
                        $sequenceArray["$objectKeysArrayKeyi"])
                ) {
                    $sequenceArray["$objectKeysArrayKeyi"][] = $valueArrayKey0;
                }

                // Add its distance to the list
                $directionsWithDistanceArray["$valueArrayKey0"]["$objectKeysArrayKeyi"] = trim($valueDistance);
                $distanceArray["$valueArrayKey0" . '-' . "$objectKeysArrayKeyi"] = trim($valueDistance);
                $distanceArray["$objectKeysArrayKeyi" . '-' . "$valueArrayKey0"] = trim($valueDistance);
            }
        }
    }

    return array(
        "map" => $sequenceArray,
        "distance" => $distanceArray,
        "directions" => $directionsArray,
        "directionWDistance" => $directionsWithDistanceArray
    );
}

?>
