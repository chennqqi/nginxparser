<?php
/**
 * Created by PhpStorm.
 * User: jorisros
 * Date: 08/12/13
 * Time: 15:37
 */

namespace jorisros\nginxparser;

require_once 'NginxElement.php';


class NginxParser {

    /**
     * Contains the values
     * @var array
     */
    protected $arrValues = array();

    protected $identity = '';

    protected $regex = null;

    protected $parent = null;

    public function __construct($identity = null, $regex = null)
    {
        $this->identity = $identity;
        $this->regex = $regex;

        return $this;
    }

    public function readFromFile($path)
    {
        $file = file_get_contents($path);
        //preg_match_all("/{.*?}/",$file,$matches);
        $lines = explode("\n",$file);
        $space = false;
        $begin_method = false;
        $end_method = false;

        $objects = array();
        $objectCounter = 0;
$l = 0;
        foreach($lines as $line)
        {
            $str = '';
            $words = array();

            $lastChar = 0;
            for($i=0; $i<strlen($line); $i++)
            {
                //var_dump($line{$i});
                switch($line{$i})
                {
                    case ' ':
                        //$word[$l] = strpos($line, $lastChar, $i);
                        $lastChar = $i;
                        $space = true;
                    break;
                    case '{':
                        $begin_method = true;
                    break;
                    case '}':
                        $end_method = true;
                    break;
                    case ';':
                    break;
                    default:
                        $str .= $line{$i};
                    break;
                }
                if($space)
                {
                    if(strlen($str) >0 )
                    {
                    $words[] = $str;
                       // var_dump($str);
                    $str = '';
                    }
                    $space = false;
                }
                if($begin_method)
                {
                    $objects[$objectCounter] = new NginxParser($words[0]);
                    $words = array();
                    //var_dump($words);
                    $begin_method = false;
                }

                if($end_method)
                {
                    $objectCounter++;
                    $end_method = false;
                }
            }
            if(isset($objects[$objectCounter]) && is_object($objects[$objectCounter]))
            {
                $command = '';
               // var_dump('line');
                if(count($words)>=1)
                {
                    $command = reset($words);
                    //var_dump($line);
                    $endPosWord = strpos($line, $command)+strlen($command);
                    $value = substr($line, $endPosWord, strlen($line));
                    $arrValue = explode(' ', str_replace(';','',$value));
                    $returnArray = array();
                    foreach($arrValue as $strVal)
                    {
                        if($strVal != '')
                        {
                            $returnArray[] = $strVal;
                        }
                    }

                    $arrChar = array();
                    $upper = false;
                    $method = $command;
                    for($i=0; $i<strlen($method); $i++)
                    {
                        if($i === 0)
                        {
                            $method{$i} = strtoupper($method{$i});
                        }
                        if($method{$i} === '_')
                        {
                           // $i++;
                            $upper = true;
                            //$arrChar[] = strtoupper($method{$i});
                        }else{
                            if($upper)
                            {
                                $arrChar[] = strtoupper($method{$i});
                                $upper = false;
                            }else{
                                $arrChar[] = $method{$i};
                            }
                        }

                    }

                    $method_name = 'set'.implode('',$arrChar);
                    //var_dump($method_name);


                    if(count($returnArray) ==1)
                    {
                        $objects[$objectCounter]->$method_name(reset($returnArray));
                    }
                    if(count($returnArray) >=2)
                    {
                        $objects[$objectCounter]->$method_name($returnArray);

                    }
                }

            }


            $l++;
        }

        return $objects;
        //print($file);
    }
    /**
     * @return bool
     */
    public function validate()
    {
        return false;
    }

    /**
     * @param $obj
     */
    protected function setParent($obj)
    {
        $this->parent = $obj;
    }

    /**
     *
     * @return string
     */
    public function build()
    {
        $first = null;

        if(is_object($this->parent))
        {
            $first = "\t";
        }

        $file = "\n".$first.$this->identity;

        if($this->regex)
        {
            $file .= " ".$this->regex;
        }
        $file .= " {\n";

        foreach($this->arrValues as $method=>$value)
        {
            switch($value)
            {
                case is_object($value):
                    $value->setParent($this);
                    $file .= $first."\t".$value."\n";
                break;
                case is_array($value):
                    $file .= $first."\t".$method."\t\t".implode(' ',$value).";\n";
                break;
                default:
                    $file .= $first."\t".$method."\t\t".$value.";\n";
                break;
            }
        }
        $file .= $first."}\n";


        return $file;
    }

    /**
     *
     * @return array
     */
    public function getValidatorErrors()
    {
        return array();
    }

    public function __toString()
    {
        return $this->build();
    }

    public function __call($method, $value)
    {
        if(substr($method, 0, 3 ) === 'get')
        {
            $arrChar = array();
            for($i=3; $i<strlen($method); $i++)
            {
                if($i === 3)
                {
                    $method{$i} = strtolower($method{$i});
                }
                if(ctype_upper($method{$i}))
                {
                    $arrChar[] = '_';
                    $arrChar[] = strtolower($method{$i});
                }else{
                    $arrChar[] = $method{$i};
                }

            }

            $method_name = implode('',$arrChar);

            return $this->arrValues[$method_name];
        }

        if(substr($method, 0, 3 ) === 'set')
        {
            $arrChar = array();
            for($i=3; $i<strlen($method); $i++)
            {
                if($i === 3)
                {
                    $method{$i} = strtolower($method{$i});
                }
                if(ctype_upper($method{$i}))
                {
                    $arrChar[] = '_';
                    $arrChar[] = strtolower($method{$i});
                }else{
                    $arrChar[] = $method{$i};
                }

            }
            $method_name = implode('',$arrChar);

            $this->arrValues[$method_name] = reset($value);
        }

        return $this;
    }

}

