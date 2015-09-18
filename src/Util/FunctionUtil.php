<?php

/*
 * This file is part of the Twig Inline Optimization Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineOptization\Util;

final class FunctionUtil
{
    /**
     * Check if a function is deterministic.
     *
     * A deterministic will return the same output (return value) for the same
     * input (arguments). Those that are not deterministic include functions
     * that use INI settings, random data, or do not produce a return value.
     *
     * @param string $name Function name
     *
     * @return bool
     */
    public static function isFunctionDeterministic($name)
    {
        switch ($name) {
            case 'strlen':
            case 'strpos':
            case 'strrpos':
            case 'stripos':
            case 'strripos':
            case 'substr':
            case 'substr_count':
            case 'strtolower':
            case 'strtoupper':
            case 'strstr':
            case 'strchr':
            case 'stristr':
            case 'strrchr':
                // These are affected by mbstring.
                if (ini_get('mbstring.func_overload') & 2) {
                    return false;
                }

                return true;

            case 'addcslashes':
            case 'addslashes':
            case 'bin2hex':
            case 'chop':
            case 'chr':
            case 'chunk_split':
            case 'count_chars':
            case 'crc32':
            case 'crypt':
            case 'explode':
            case 'hex2bin':
            case 'implode':
            case 'join':
            case 'lcfirst':
            case 'levenshtein':
            case 'ltrim':
            case 'md5':
            case 'metaphone':
            case 'nl2br':
            case 'number_format':
            case 'ord':
            case 'rtrim':
            case 'sha1':
            case 'similar_text':
            case 'soundex':
            case 'sprintf':
            case 'str_get_csv':
            case 'str_ireplace':
            case 'str_pad':
            case 'str_repeat':
            case 'str_rot13':
            case 'str_split':
            case 'str_word_count':
            case 'strcasecmp':
            case 'strcmp':
            case 'strcspn':
            case 'strip_tags':
            case 'stripcslashes':
            case 'stripslashes':
            case 'strnatcasecmp':
            case 'strnatcmp':
            case 'strncasecmp':
            case 'strncmp':
            case 'strpbrk':
            case 'strrev':
            case 'strspn':
            case 'strtr':
            case 'substr_compare':
            case 'substr_replace':
            case 'trim':
            case 'ucfirst':
            case 'ucwords':
            case 'vsprintf':
            case 'wordwrap':
                return true;

            // Math
            case 'abs':
            case 'acos':
            case 'acosh':
            case 'asin':
            case 'asinh':
            case 'atan2':
            case 'atan':
            case 'atanh':
            case 'base_convert':
            case 'bindec':
            case 'ceil':
            case 'cos':
            case 'cosh':
            case 'decbin':
            case 'dechex':
            case 'decoct':
            case 'deg2rad':
            case 'deg2rad':
            case 'exp':
            case 'expm1':
            case 'floor':
            case 'fmod':
            case 'hexdec':
            case 'hypot':
            case 'intdiv':
            case 'is_finite':
            case 'is_infinite':
            case 'is_nan':
            case 'log10':
            case 'log1p':
            case 'log':
            case 'max':
            case 'min':
            case 'octdec':
            case 'pi':
            case 'pow':
            case 'rad2deg':
            case 'round':
            case 'sin':
            case 'sinh':
            case 'sqrt':
            case 'tan':
            case 'tanh':
                return true;
        }

        var_dump($name);

        return false;
    }
}
