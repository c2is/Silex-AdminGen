<?php

class CrudableBehaviorUtils
{
    const TAB_CHARACTER = "\t";

    static public function camelize($string)
    {
        $string = preg_replace("/([_-\s]?([a-zA-Z0-9]+))/e",
            "ucwords('\\2')",
            $string
        );

        return strtoupper($string[0]) . substr($string, 1);
    }

    static public function getModelClassname($namespace, $tableName)
    {
        return sprintf("\\%s\\%s",
            $namespace,
            CrudableBehaviorUtils::camelize($tableName)
        );
    }

    static public function getFormClassname($namespace, $tableName)
    {
        return str_replace('Model', "Form\\Type", sprintf("\\%s\\%sType",
            $namespace,
            CrudableBehaviorUtils::camelize($tableName)
        ));
    }

    static public function getQueryClassname($namespace, $tableName)
    {
        return sprintf("\\%s\\%sQuery",
            $namespace,
            CrudableBehaviorUtils::camelize($tableName)
        );
    }

    static public function formatArrayToString($options, $iteration = 0)
    {
        $iteration++;

        $ouput = '';
        if (is_array($options)) {
            if ($iteration == 1) {
                $ouput .= sprintf("array(\n");
            }

            foreach ($options as $key => $value) {
                if (is_array($value)) {
                    $ouput .= sprintf("%s'%s' => array(\n%s", str_repeat(self::TAB_CHARACTER, $iteration +2), $key, CrudableBehaviorUtils::formatArrayToString($value, $iteration +2));
                }
                else {
                    if (is_bool($value)) {
                        $ouput .= sprintf("%s'%s' => %s,\n", str_repeat(self::TAB_CHARACTER, $iteration +2), $key, $value ? 'true' : 'false');
                    }
                    else if (is_int($key)) {
                        if (strpos($value, 'new ') === 0) {
                            $ouput .= sprintf("%s%s,\n", str_repeat(self::TAB_CHARACTER, $iteration +2), $value);
                        }
                        else {
                            $ouput .= sprintf("%s%s => '%s',\n", str_repeat(self::TAB_CHARACTER, $iteration +2), $key, $value);
                        }
                    }
                    else {
                        $ouput .= sprintf("%s'%s' => '%s',\n", str_repeat(self::TAB_CHARACTER, $iteration +2), $key, $value);
                    }
                }
            }

            $ouput .= sprintf("%s)", str_repeat(self::TAB_CHARACTER, $iteration +1));
            if ($iteration > 1) {
                $ouput .= ",\n";
            }
        }

        return $ouput;
    }
}
