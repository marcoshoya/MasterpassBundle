<?php

namespace Hoya\MasterpassBundle\Common;

class Serializer
{

    private static $Data;

    private function GetaArray($arrayValue)
    {
        foreach ($arrayValue as $Member) {
            $this->SerializeClass($Member, get_class($Member));
        }
    }

    public function Serialize($ObjectInstance, $ClassName)
    {
        Serializer::$Data.="<Root>";
        $this->SerializeClass($ObjectInstance, $ClassName);
        Serializer::$Data.="</Root>";

        return Serializer::$Data;
    }

    public function SerializeClass($ObjectInstance, $ClassName)
    {
        Serializer::$Data.="<" . $ClassName . ">";
        $Class = new \ReflectionClass($ClassName);
        $ClassArray = array($ObjectInstance);
        $Properties = $Class->getProperties();
        $i = 0;
        foreach ($ClassArray as $ClassMember) {
            $prpName = $Properties[$i]->getName();
            Serializer::$Data.="<" . $prpName . ">";
            $prpType = gettype($ClassMember);


            if ($prpType == 'object') {
                $serializerinstance = new Serializer();
                $serializerinstance->SerializeClass($ClassMember, get_class($ClassMember));
            }
            if ($prpType == 'array') {
                $this->GetaArray($ClassMember);
            } else {
                Serializer::$Data.=$ClassMember;
            }
            Serializer::$Data.="</" . $prpName . ">";
            $i++;
        }
        Serializer::$Data.="</" . $ClassName . ">";

        return Serializer::$Data;
    }

    public function WriteXmlFile($XmlData, $FilePath)
    {
        $Xml = simplexml_load_string($XmlData);
        $Doc = new \DOMDocument();
        $Doc->loadXML($Xml->asXML());
        $Doc->save($FilePath);
    }

    public function DeserializeClass($xmlString)
    {
        $Xml = simplexml_load_string($xmlString, Account());
        return $this->Deserialize($Xml);
    }

    public function Deserialize($Root)
    {
        $result = null;
        $counter = 0;
        foreach ($Root as $member) {
            $instance = new \ReflectionClass($member->getName());
            $ins = $instance->newInstance();
            foreach ($member as $child) {
                $rp = $instance->getMethod("set_" . $child->getName());
                if (count($child->children()) == 0) {
                    $rp->invoke($ins, $child);
                } else {
                    $rp->invoke($ins, $this->Deserialize($child->children()));
                    echo $child;
                }
            }
            if (count($Root) == 1) {

                return $ins;
            } else {
                $result[$counter] = $ins;
                $counter++;
            }
            if ($counter == count($Root)) {

                return $result;
            }
        }
    }

}
