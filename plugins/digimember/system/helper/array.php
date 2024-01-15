<?php

function ncore_explodeKeyValuePairs( $params ) {

	$result=array();

	foreach (explode("\n", $params) as $line)
	{
		$line = trim($line);

		if ($pos=strpos($line,'='))
		{
			$key = substr($line, 0, $pos);
			$value = substr($line, $pos+1);
			$result[ $key ] = $value;
		}
	}
	return $result;
}


function ncore_elementsWithKey($array, $key, $value, $keep_keys=false )
{
	$result = array();

	foreach ($array as $index => $one)
	{
		$one_value = ncore_retrieve($one, $key);

		$matches = $one_value == $value;

		if ($matches)
		{
			if ($keep_keys)
			{
                $result[$index] = $one;
			}
			else
			{
                $result[] = $one;
			}
		}
	}
	return $result;
}

function ncore_findByKey($array, $key, $value, $default=false)
{
    $found = ncore_elementsWithKey( $array, $key, $value );

    return $found
           ? $found[0]
           : $default;
}

function ncore_indexOfByKey($array, $key, $value)
{
	foreach ($array as $index => $one)
	{
		$one_value = ncore_retrieve($one, $key);
		$cmp_value = $value;

		$both_numeric = is_numeric( $one_value ) && is_numeric( $cmp_value );
		if ($both_numeric)
		{
			$one_value += 0;
			$cmp_value += 0;
		}

		$matches = $one_value === $cmp_value;

		if ($matches)
		{
			return $index;
		}
	}

	return false;
}

function ncore_retrieveValues( $list, $key_column ) {

    $result = array();

    foreach ($list as $element) {
        $value =  ncore_retrieve( $element, $key_column );
        $result[] = $value;
    }
    return $result;
}

function ncore_listToArray( $list, $key_column, $value_column, $optgroup_column = false )
{
	$result = array();

    $current_optgroup = false;
    $optgroup_count   = 0;

	foreach ($list as $index => $element)
	{
        if ($optgroup_column) {
            $optgroup = ncore_retrieve( $element, $optgroup_column, false );
            if ($optgroup && $current_optgroup != $optgroup)
            {
                $optgroup_count++;
                $result[ "optgroup_$optgroup_count"] = $optgroup;
                $current_optgroup = $optgroup;
            }
        }

		$key = $key_column==='use_index'
               ? $index
               : ncore_retrieve( $element, $key_column );

		$value = ncore_retrieve( $element, $value_column );

		$result[ $key ] = $value;
	}

	return $result;
}

function ncore_listToArraySorted( $list, $key_column, $value_column )
{
	$strtolower = function_exists( 'mb_strtolower' )
				? 'mb_strtolower'
				: 'strtolower';

	$keys = array();
	$values = array();
	$sort = array();
	foreach ($list as $element)
	{
		$keys[] = ncore_retrieve( $element, $key_column );

		$value = ncore_retrieve( $element, $value_column );;
		$values[] = $value;
		$sort[] = $strtolower( $value );
	}

	array_multisort( $sort, $values, $keys );

	$result = array();
	foreach ($keys as $index => $key)
	{
		$result[ $key ] = $values[$index];
	}
	return $result;
}

function ncore_simpleMapExplode( $value )
{
	$values = array();
	while ($value)
	{
	   if ($value[0] === ',')
	   {
		   $value = substr( $value, 1 );
		   if (!$value)
		   {
			   break;
		   }
	   }

		$pos = strpos( $value, ':' );
		$id = substr( $value, 0, $pos );
		$value = substr( $value, $pos+1 );

		$have_quote = $value[0] == '"';
		if (!$have_quote)
		{
			trigger_error( 'Invalid value' );
			return array();
		}

		$pos = 1;
		$is_escaped = true;
		while ($is_escaped)
		{
			$pos = strpos( $value, '"', $pos );
			$is_escaped = $pos !== false
					   && $value[ $pos-1 ] == "\\";
		}

		$reached_end = $pos===false;

		$string = $reached_end
				? substr( $value, 1 )
				: substr( $value, 1, $pos-1 );

	   $values[ $id ] = $string;

	   $value = $reached_end
			  ? ''
			  : substr( $value, $pos+1 );

	   if ($value && $value[0] === ',')
	   {
		   $value = substr( $value, 1 );
	   }
	}

	return $values;
}

function ncore_simpleMapImplode( $values )
{
	if (!is_array($values))
	{
		return '';
	}

	$value = '';
	foreach ($values as $key => $val)
	{
		if ($value)
		{
			$value .= ',';
		}

		$val = str_replace( '"', '', $val );

		$value .= "$key:\"$val\"";
	}
}

function ncore_sortOptions( $options )
{
    if (!is_array($options) || empty($options)) {
        return array();
    }

	$have_mb = function_exists('mb_strtolower');

	$ids = array();
	$labels = array();
	$sort = array();

	$result = array();
	if (isset($options['NULL']))
	{
		$result['NULL'] = $options['NULL'];
		unset( $options['NULL'] );
	}
	if (isset($options[0]))
	{
		$result[0] = $options[0];
		unset( $options[0] );
	}

	foreach ($options as $id => $label)
	{
		$ids[] = $id;
		$labels[] = $label;
		$sort[] = $have_mb
				? mb_strtolower( $label )
				: strtolower( $label );
	}

	array_multisort( $sort, SORT_STRING, SORT_ASC, $ids, $labels );

	foreach ($ids as $index => $id)
	{
		$result[ $id ] = $labels[ $index ];
	}
	return $result;
}

function ncore_isNumericArray($array)
{
	if (!is_array($array))
	{
		return false;
	}

	if (!$array)
	{
		return true;
	}

	if (!isset($array[ 0 ]))
	{
		return false;
	}

	$keys = array_keys($array);

	foreach ($keys as $index => $key)
	{
		if (!is_numeric($key) || $index != $key)
		{
			return false;
		}
	}

	return true;
}

function ncore_purgeArray( $array, $key_map, $level=0 )
{
	$result = array();

	foreach ($array as $key => $element)
	{
		if (is_numeric( $key ))
		{
			$mapped_key    = $key;
			$keep_key      = true;
			$level_offset  = 0;
		}
		else
		{
			$mapped_key   = ncore_retrieve( $key_map, $key, false );
			$keep_key     = $mapped_key !== false;
			$level_offset = 1;
		}

		if (!$keep_key )
		{
			continue;
		}

		if ($level+$level_offset>0)
		{
			$result[ $mapped_key ] = $element;
			continue;
		}

		$have_object = is_object( $element );
		if ($have_object)
		{
			$element = (array) $element;
		}

		if (is_array( $element ))
		{
			$element = ncore_purgeArray( $element, $key_map, $level+$level_offset );
		}

		if ($have_object)
		{
			$element = (object) $element;
		}

		$result[ $mapped_key ] = $element;
	}

	return $result;
}

function ncore_explodeAndPurgeArray( $seperator_or_list, $text_with_seperators )
{
    if (is_array( $seperator_or_list )) {
        $seperator = array_shift( $seperator_or_list );

        if ($seperator_or_list) {
            $text_with_seperators = str_replace( $seperator_or_list, $seperator, $text_with_seperators );
        }
    }
    else {
        $seperator = $seperator_or_list;
    }

    $list_raw = explode( $seperator, $text_with_seperators );
    $list = array();
    foreach ($list_raw as $one) {
        $one = trim( $one );

        if ($one) {
            $list[] = $one;
        }
    }
    return $list;
}

function ncore_flattenAssocArray($array, &$ref, $path = null) {
    $keys = [];

    foreach($array as $k => $v) {
        $keys[] = $k;

        if(is_null($path)){
            $_p = $k;
        } else {
            $_p = $path . '.' . $k;
        }

        if (is_array($array[$k])) {
            $keys = array_merge($keys, ncore_flattenAssocArray($array[$k], $ref, $_p));
        } else {
            $ref[$_p] = $v;
        }
    }

    return $keys;
}