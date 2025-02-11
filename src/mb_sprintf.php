<?php
/**
 * polyfill for `mb_sprintf` and `mb_vsprintf`
 *
 * author: Viktor Söderqvist <viktor@textalk.se>
 *
 * upstream source: https://www.php.net/manual/en/function.sprintf.php#89020
 */

namespace Rocketman\Polyfill;

final class mb_sprintf {
  /**
   * Works with all encodings in format and arguments.
   * Supported: Sign, padding, alignment, width and precision.
   * Not supported: Argument swapping.
   */
  public static function mb_vsprintf($format, $argv, $encoding=null) {
      if (is_null($encoding))
          $encoding = mb_internal_encoding();

      // Use UTF-8 in the format so we can use the u flag in preg_split
      $format = mb_convert_encoding($format, 'UTF-8', $encoding);

      $newformat = ""; // build a new format in UTF-8
      $newargv = array(); // unhandled args in unchanged encoding

      while ($format !== "") {
      
        // Split the format in two parts: $pre and $post by the first %-directive
        // We get also the matched groups
        @list ($pre, $sign, $filler, $align, $size, $precision, $type, $post) =
            preg_split("!\%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u",
                       $format, 2, PREG_SPLIT_DELIM_CAPTURE) ;

        $newformat .= mb_convert_encoding($pre, $encoding, 'UTF-8');
        
        if ($type == '') {
          // didn't match. do nothing. this is the last iteration.
          $newargv = array_merge($newargv, array_values($argv));
          break;
        }
        elseif ($type === '%') {
          // an escaped %
          $newformat .= mb_convert_encoding('%%', $encoding, 'UTF-8');
        }
        elseif ($type === 's') {
          $arg = (string) array_shift($argv);
          $arg = mb_convert_encoding($arg, 'UTF-8', $encoding);
          $padding_pre = '';
          $padding_post = '';
          
          // truncate $arg
          if ($precision !== '') {
            $precision = intval(substr($precision,1));
            if ($precision > 0 && mb_strlen($arg,$encoding) > $precision)
              $arg = mb_substr($arg,0,$precision,$encoding);
          }
          
          // define padding
          if ($size > 0) {
            $arglen = mb_strlen($arg, $encoding);
            if ($arglen < $size) {
              if($filler==='')
                  $filler = ' ';
              if ($align == '-')
                  $padding_post = str_repeat($filler, $size - $arglen);
              else
                  $padding_pre = str_repeat($filler, $size - $arglen);
            }
          }
          
          // escape % and pass it forward
          $newformat .= mb_convert_encoding($padding_pre . str_replace('%', '%%', $arg) . $padding_post, $encoding, 'UTF-8');
        }
        else {
          // another type, pass forward
          $newformat .= mb_convert_encoding("%$sign$filler$align$size$precision$type", $encoding, 'UTF-8');
          $newargv[] = array_shift($argv);
        }
        $format = strval($post);
      }
      return vsprintf($newformat, $newargv);
  }
}
