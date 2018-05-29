<div class="row">
	<div class="col-md-4">

<dl class="dl-horizontal">
	<dt>Status</dt>
	<dd>{{#if data.status}}{{{colorlabel data.status}}}{{/if}}</dd>
	<dt>Type</dt>
	<dd>{{#if data.type}}{{{colorlabel data.type}}}{{/if}}</dd>
</dl>

	</div>
	<div class="col-md-4">

<dl class="dl-horizontal">
	<dt>Created</dt>
	<dd data-datetime="<?= ($this->scaffold->object->created) ? : '' ?>"><?= ($this->scaffold->object->created) ? : '-' ?></dd>
	<dt>Updated</dt>
	<dd data-datetime="<?= ($this->scaffold->object->updated) ? : '' ?>"><?= ($this->scaffold->object->updated) ? : '-' ?></dd>
</dl>

	</div>
</div>