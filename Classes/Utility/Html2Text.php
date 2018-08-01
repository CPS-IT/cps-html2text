<?php
namespace CPSIT\CpsHtml2Text\Utility;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class Html2Text
 *
 * @package CPSIT\CpsHtml2Text\Utility
 */
class Html2Text
{
    /**
     * Typoscript configuration
     *
     * @var array
     */
    protected $conf = [];

    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var string
     */
    protected $output = '';

    /**
     * Tries to convert the given HTML into a plain text format - best suited for
     * e-mail display, etc.
     *
     * <p>In particular, it tries to maintain the following features:
     * <ul>
     *   <li>Links are maintained, with the 'href' copied over
     *   <li>Information in the &lt;head&gt; is lost
     * </ul>
     *
     * @param string $html the input HTML
     * @param $conf
     * @return string the HTML converted, as best as possible, to text
     */
    public function convert($html, $conf)
    {
        $this->setConf($conf);

        $is_office_document = $this->isOfficeDocument($html);

        if ($is_office_document) {
            // remove office namespace
            $html = str_replace(["<o:p>", "</o:p>"], "", $html);
        }

        $html = $this->fixNewlines($html);
        if (mb_detect_encoding($html, "UTF-8", true)) {
            $html = mb_convert_encoding($html, "HTML-ENTITIES", "UTF-8");
        }

        $doc = $this->getDocument($html, $this->conf['ignoreLibXmlErrors']);

        $output = $this->iterateOverNode($doc, null, false);

        // process output for whitespace/newlines
        $output = $this->processWhitespaceNewlines($output);

        return $output;
    }

    /**
     * Unify newlines; in particular, \r\n becomes \n, and
     * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
     * all become \ns.
     *
     * @param string $text text with any number of \r, \r\n and \n combinations
     * @return string the fixed text
     */
    public function fixNewlines($text)
    {
        // replace \r\n to \n
        $text = str_replace("\r\n", "\n", $text);
        // remove \rs
        $text = str_replace("\r", "\n", $text);

        return $text;
    }

    /**
     * Remove leading or trailing spaces and excess empty lines from provided multiline text
     *
     * @param string $text multiline text any number of leading or trailing spaces or excess lines
     * @return string the fixed text
     */
    public function processWhitespaceNewlines($text)
    {

        // remove excess spaces around tabs
        $text = preg_replace("/ *\t */im", "\t", $text);

        // remove leading whitespace
        $text = ltrim($text);

        // remove leading spaces on each line
        $text = preg_replace("/\n[ \t]*/im", "\n", $text);

        // convert non-breaking spaces to regular spaces to prevent output issues,
        // do it here so they do NOT get removed with other leading spaces, as they
        // are sometimes used for indentation
        $text = str_replace("\xc2\xa0", " ", $text);

        // remove trailing whitespace
        $text = rtrim($text);

        // remove trailing spaces on each line
        $text = preg_replace("/[ \t]*\n/im", "\n", $text);

        // unarmor pre blocks
        $text = $this->fixNewLines($text);

        // remove unnecessary empty lines
        $text = preg_replace("/\n\n\n*/im", "\n\n", $text);

        return $text;
    }

    /**
     * Parse HTML into a DOMDocument
     *
     * @param string $html the input HTML
     * @param boolean $ignore_error Ignore xml parsing errors
     * @return \DOMDocument the parsed document tree
     * @throws \Exception
     */
    public function getDocument($html, $ignore_error = false)
    {
        $doc = new \DOMDocument();

        $html = trim($html);

        if (!$html) {
            // DOMDocument doesn't support empty value and throws an error
            // Return empty document instead
            return $doc;
        }

        if ($html[0] !== '<') {
            // If HTML does not begin with a tag, we put a body tag around it.
            // If we do not do this, PHP will insert a paragraph tag around
            // the first block of text for some reason which can mess up
            // the newlines. See pre.html test for an example.
            $html = '<body>' . $html . '</body>';
        }

        if ($ignore_error) {
            $doc->strictErrorChecking = false;
            $doc->recover = true;
            $doc->xmlStandalone = true;
            $old_internal_errors = libxml_use_internal_errors(true);
            $load_result = $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET | LIBXML_PARSEHUGE);
            libxml_use_internal_errors($old_internal_errors);
        } else {
            $load_result = $doc->loadHTML($html);
        }

        if (!$load_result) {
            throw new \Exception("Could not load HTML - badly formed?", 1533044996);
        }

        return $doc;
    }

    /**
     * Can we guess that this HTML is generated by Microsoft Office?
     *
     * @param string $html
     * @return bool
     */
    public function isOfficeDocument($html)
    {
        return strpos($html, "urn:schemas-microsoft-com:office") !== false;
    }

    /**
     * Is white space
     *
     * @param string $text
     * @return bool
     */
    public function isWhitespace($text)
    {
        return strlen(trim($text, "\n\r\t ")) === 0;
    }

    /**
     * Get next node child name
     *
     * @param \DOMText $node
     * @return null|string
     */
    public function nextChildName($node)
    {
        // get the next child
        $nextNode = $node->nextSibling;
        while ($nextNode != null) {
            if ($nextNode instanceof \DOMText) {
                if (!$this->isWhitespace($nextNode->wholeText)) {
                    break;
                }
            }
            if ($nextNode instanceof \DOMElement) {
                break;
            }
            $nextNode = $nextNode->nextSibling;
        }
        $nextName = null;
        if (($nextNode instanceof \DOMElement || $nextNode instanceof \DOMText) && $nextNode != null) {
            $nextName = strtolower($nextNode->nodeName);
        }

        return $nextName;
    }

    /**
     * Parse nodes
     *
     * @param \DOMText $node
     * @param null $prevName
     * @param bool $in_pre
     * @return string
     */
    public function iterateOverNode($node, $prevName = null, $in_pre = false)
    {
        if (!isset($output)) {
            $output = '';
        }

        if ($node instanceof \DOMText) {
            // Replace whitespace characters with a space (equivilant to \s)
            if ($in_pre) {
                $text = "\n" . trim($node->wholeText, "\n\r\t ") . "\n";
                // Remove trailing whitespace only
                $text = preg_replace("/[ \t]*\n/im", "\n", $text);

                // armor newlines with \r.
                return str_replace("\n", "\r", $text);
            } else {
                $text = preg_replace("/[\\t\\n\\f\\r ]+/im", " ", $node->wholeText);
                if (!$this->isWhitespace($text) && ($prevName == 'p' || $prevName == 'div')) {
                    return "\n" . $text;
                }

                return $text;
            }
        }

        if ($node instanceof \DOMDocumentType) {
            // ignore
            return "";
        }
        if ($node instanceof \DOMProcessingInstruction) {
            // ignore
            return "";
        }

        $name = strtolower($node->nodeName);
        //$nextName = $this->nextChildName($node);

        if (in_array($name, $this->conf['ignoreTags'])) {
            return "";
        }

        if (isset($node->childNodes)) {
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                $n = $node->childNodes->item($i);
                $text = $this->iterateOverNode($n);
                $output .= $text;
            }
        }
        if (array_key_exists($name . '.', $this->conf)) {
            if (isset($this->conf[$name . '.']['attributes'])) {
                $attributes = preg_split('/\s*,\s*/', $this->conf[$name . '.']['attributes']);
                foreach ($attributes as $attr) {
                    $this->cObj->cObjGetSingle('LOAD_REGISTER', [$attr => $node->getAttribute($attr)]);
                }
            } else {
                $attributes = [];
            }

            $output = $this->cObj->stdWrap($output, $this->conf[$name . '.']);

            foreach ($attributes as $attr) {
                $this->cObj->cObjGetSingle('RESTORE_REGISTER', []);
            }
        }

        if (in_array($name, $this->conf['blockElements'])) {
            $output = "\n" . $output . "\n";
        }
        if ($name == 'br') {
            $output .= "\n";
        }

        return $output;
    }

    /**
     * Get typoscript configuration
     *
     * @return array
     */
    public function getConf(): array
    {
        return $this->conf;
    }

    /**
     * Set Typoscript configuration
     *
     * @param array $conf
     */
    public function setConf(array $conf)
    {
        // Tags to be removed
        if (isset($conf['ignoreTags'])) {
            $conf['ignoreTags'] = preg_split('/\s*,\s*/', $conf['ignoreTags']);
        }
        /*
         * A list of HTML tags that are considered to be block elements
         * (line break will be inserted before and after tag content)
         */
        if (isset($conf['blockElements'])) {
            $conf['blockElements'] = preg_split('/\s*,\s*/', $conf['blockElements']);
        }

        if (empty($conf['ignoreLibXmlErrors'])) {
            $conf['ignoreLibXmlErrors'] = false;
        } else {
            $conf['ignoreLibXmlErrors'] = true;
        }

        $this->conf = $conf;
    }
}
