<?php

class Xnary {
	// X = 62
	static public $range = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
	// X = 2
	//static public $range = '01';

	static function toInt( $str ) {
		$range = self::$range;
		$base = strlen($range);

		$in = array_reverse(str_split($str));

		$out = 0;

		$location = 0;
		foreach ( $in AS $char ) {
			$index = strpos($range, $char);
			if ( false !== $index ) {
				$index += 1;

				$charValue = $index * pow($base, $location);
				$out += $charValue;

				$location++;
			}
		}

		return $out;
	}

	static function toString( $int ) {
		$range = self::$range;
		$base = strlen($range);
		$range = str_split($range);

		$int -= 1;

		$size = ceil(log($int, $base)) - 1;
		$size = floor(log(($base-1)*$int/$base+1, $base)) + 1;

		for ( $i=1; $i<$size; $i++ ) {
			$int -= pow($base, $i);
		}

		$out = '';

		for ( $location=$size-1; $location>=0; $location-- ) {
			$locValue = pow($base, $location);
			$charValue = floor($int / $locValue);

			$out .= $range[$charValue];

			$int -= $charValue * $locValue;
		}

		return $out;
	}

	static function fromInt( $int ) {
		return self::toString($int);
	}

	static function fromString( $str ) {
		return self::toInt($str);
	}

}


