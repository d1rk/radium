<?php
$content = $this->scaffold->object;

echo $this->scaffold->render('meta', ['data' => $content->data()]);
echo '<p style="width: 70px; text-align: center; padding: 2px; background: white; margin-left: 45%;">Preview</p>';
echo '<hr style="margin-top: -23px;" />';

switch($content->type) {
	case 'plain':
		echo sprintf('<div class="plaintext"><pre>%s</pre></div>', $content->body());
	break;
	case 'markdown':
		echo sprintf('<div class="well markdown">%s</div>', $content->body());
	break;
	case 'mustache':
		echo $content->body($this->_data);
	break;
	case 'html':
	default:
		echo $content->body();
}

echo '<hr />';
echo $this->scaffold->render('data', ['data' => \lithium\util\Set::flatten($this->scaffold->object->data())]);
