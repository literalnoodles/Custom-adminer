<?php

/** Pretty print JSON values in display
*/
class AdminerJsonField {
	/** @var AdminerPlugin */
	public function __construct() {
	}

	private function _testJson($value) {
		if ((substr($value, 0, 1) == '{' || substr($value, 0, 1) == '[') && ($json = json_decode($value, true))) {
			return $json;
		}
		return $value;
	}

    function selectVal($val, $link, $field, $original) {
        $return = ($val === null ? "<i>NULL</i>" : (preg_match("~char|binary|boolean~", $field["type"]) && !preg_match("~var~", $field["type"]) ? "<code>$val</code>" : $val));
		if (preg_match('~blob|bytea|raw|file~', $field["type"]) && !is_utf8($val)) {
			$return = "<i>" . lang('%d byte(s)', strlen($original)) . "</i>";
		}
		if (preg_match('~json~', $field["type"])) {
			$return = "<code class='jush-js'>$return</code>";
		}

        // modify part
        if ($this->_testJson($original) !== $original) {
            $jsonText = json_encode(json_decode($original, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $return = <<<HTML
             <textarea cols="50" rows="20">$jsonText</textarea>
            HTML;
        }
        // end modify

		return ($link ? "<a href='" . h($link) . "'" . (is_url($link) ? target_blank() : "") . ">$return</a>" : $return);
    }
}