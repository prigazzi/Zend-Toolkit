<?php
/**
 * Utilities Class.
 */
class Toolkit_Utils_Normalize {
 
	/**
	 * Normalize String for URL Slug.
	 *
	 * @param String to normalize
	 * @return Normalized string with special Spanish characters replaced
	 */
	public function normalize($string)
	{
		return strtr(strtolower(utf8_decode($string)), utf8_decode('áéíóúñÑ'), 'aeiounn');
	}
}