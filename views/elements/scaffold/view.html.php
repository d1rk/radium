<?= $this->scaffold->render('meta', ['data' => $this->scaffold->object->data()]); ?>
<hr />
<?= $this->scaffold->render('data', ['data' => \lithium\util\Set::flatten($this->scaffold->object->data())]); ?>