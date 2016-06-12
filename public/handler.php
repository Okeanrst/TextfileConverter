<?php

//error_reporting(E_ALL);
ini_set("display_errors", 0);

ini_set('upload_max_size' , '6M');
ini_set('post_max_size', '5M');

require '../vendor/autoload.php';
require '../vendor/json2xml/src/Processus/Serializer/XmlRpcValue.php';
require '../vendor/okeanrst/common/src/prepareDataToCsv.php';

use Symfony\Component\Yaml\Yaml as YamlParser;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

$errormsg = [];

if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $isAjax = true;
} else {
    $isAjax = false;
}

if (isset($_POST['send']) || $isAjax) {   
    if (isset($_POST['format']) && !empty($_POST['format'])) {
        $outFormat = strtolower($_POST['format']);
        if (!in_array($outFormat, ['json', 'xml', 'yaml', 'csv'])) {
            array_push($errormsg, 'Invalid format for the output file.');
        }
    }
    if (count($errormsg) === 0) {             
        if (isset($_FILES['inputfile'])) {
            $error = $_FILES['inputfile']['error'];        
            if ($error == UPLOAD_ERR_OK) {
                if ($_FILES['inputfile']['size'] === 0) {
                    array_push($errormsg, 'Error. The selected file is empty.');                
                } else {
                    $tmp_name = $_FILES['inputfile']['tmp_name'];
                    if (is_uploaded_file($tmp_name)) {
                        $name = basename($_FILES['inputfile']['name']);
                        $dot = strrpos($name, '.');
                        $idleName = substr($name, 0, $dot);                        
                        $extention = substr($name, $dot + 1);
                    } else {
                        array_push($errormsg, "File upload error.");
                    }         
                }                
            } else {
                switch ($error) {
                    case '1':
                        array_push($errormsg, "The size of the uploaded file exceeds the allowed size.");
                        break;
                            
                    case '2':
                        array_push($errormsg, "The size of the uploaded file exceeds the allowed size.");
                        break;

                    case '3':
                        array_push($errormsg, "The uploaded file was only partially uploaded.");
                        break;                        

                    default:
                        array_push($errormsg, "File upload error.");
                        break;
                }
            }
        } else {
            array_push($errormsg, "File is not selected.");
        }              
    }

    if (strtolower($extention) == $outFormat) {
        array_push($errormsg, "Extantion of the incoming file matches the required format.");
    }   
    
    if (count($errormsg) === 0) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);        
        $type = finfo_file($finfo, $tmp_name);        
        finfo_close($finfo);
    
        if (in_array($type, ['text/plain', 'application/json', 'text/csv', 'application/xml', 'text/yaml'])) {
            try {
                if ($type == 'application/xml') {
                    $inputMimeType = 'xml';
                    $handle = fopen($tmp_name, 'r');
                    $xmlString = fread($handle, filesize($tmp_name));
                    fclose($handle);                    
                    $reader = new Zend\Config\Reader\XML();
                    $dataArray   = $reader->fromString($xmlString);                    
                } elseif ($type == 'application/json' || $type == 'text/plain' && $extention == 'json') {
                    $inputMimeType = 'json';
                    $handle = fopen($tmp_name, 'r');
                    $jsonString = fread($handle, filesize($tmp_name));
                    fclose($handle);
                    $dataArray = json_decode($jsonString, true);                    
                } elseif ($type == 'text/csv' || $type == 'text/plain' && $extention == 'csv') {
                    $inputMimeType = 'csv';                    
                    $csvObj = new mnshankar\CSV\CSV();
                    $dataArray = $csvObj->fromFile($tmp_name)->toArray();                    
                } elseif ($type == 'text/yaml' || $type == 'text/plain' && $extention == 'yaml') {
                    $inputMimeType = 'yaml';                    
                    try {
                        $dataArray = YamlParser::parse(file_get_contents($tmp_name), YamlParser::PARSE_DATETIME);
                    } catch (YamlParseException $e) {
                        sprintf("Unable to parse the YAML string: %s", $e->getMessage());
                        array_push($errormsg, sprintf("Unable to parse the YAML string: %s", $e->getMessage()));
                    }                                                     
                }

                switch ($outFormat) {
                    case 'xml':
                        $newName = 'data/'.$idleName.'.xml';
                        $handle = fopen($newName, 'w');                                                    
                        $serializer = new \Processus\Serializer\XmlRpcValue();
                        $serializer->setEncoding('UTF-8');
                        $xmlString = $serializer->encode($dataArray);                        
                        fwrite($handle, $xmlString);
                        fclose($handle);
                        break;
                    
                    case 'json':
                        $newName = 'data/'.$idleName.'.json';
                        $handle = fopen($newName, 'w');                        
                        $jsonString = json_encode($dataArray);                        
                        $newName = 'data/'.$idleName.'.json';
                        $handle = fopen($newName, 'w');
                        fwrite($handle, $jsonString);
                        fclose($handle);
                        break;

                    case 'csv':
                        $newName = 'data/'.$idleName.'.csv';                                                
                        if ($inputMimeType == 'yaml' || $inputMimeType == 'xml') {                            
                            $prepareArray = prepareDataXmlYamlToCsv($dataArray);                            
                            $config = new ExporterConfig();
                            $exporter = new Exporter($config);                      
                            $exporter->export($newName, $prepareArray);                                                    
                        } elseif ($inputMimeType == 'json') {                            
                            $prepareArray = prepareDataJsonToCsv($dataArray);
                            $config = new ExporterConfig();
                            $exporter = new Exporter($config);                      
                            $exporter->export($newName, $prepareArray);
                        }
                        break;

                    case 'yaml':
                        $newName = 'data/'.$idleName.'.yaml';
                        $handle = fopen($newName, 'w');                        
                        fwrite($handle, YamlParser::dump($dataArray, 5, 4));                        
                        fclose($handle);
                        break;
                }
            } catch (Exception $e) {
                array_push($errormsg, "File conversion error.");
            }
            
        } else {            
            array_push($errormsg, 'Invalid mimetype input file');            
        }      
    }
    
    if($isAjax) {
        if (count($errormsg) > 0) {
            $errors = '<div class="error"><p>Errors:</p><ul>';
            foreach ($errormsg as $error) {
                $errors = $errors.'li'.$error.'/li';
            }
            $errors = $error.'</ul></div>';
            echo json_encode($errors);
        } else {
            $message = '<div class="success"><p>File has been successfully converted.</p>'.
                 '<p><a href="'.$newName.'">Download the converted file</a></p></div>';
            echo json_encode($message);
        }
        exit;
    }
} else {    
    header("Location: /");
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>File conversion status</title>
    <link href="/css/style.css" media="screen" rel="stylesheet" type="text/css">  
  </head>
  <body>
   <?php if (count($errormsg) === 0) {
       echo '<div class="success"><p>file has been successfully converted.</p>';
       echo '<p><a href="'.$newName.'">Download the converted file</a></p></div>';
   } else {
       echo '<div class="error"><p>The file was not converted. The following error occurred:</p>';
       foreach ($errormsg as $msg) {
           echo '<p>'.$msg.'</p>';
       }
       echo '</div>';
   } ?>
   <div><a href="/">Back</a></div>
  </body>
</html>