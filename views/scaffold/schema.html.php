<?= $this->html->style('/radium/css/scaffold', array('inline' => false)); ?>

<ol class="breadcrumb">
	<li>
		<i class="fa fa-home fa-fw"></i>
		<?= $this->html->link('Home', '/');?>
	</li>
	<?php if ($this->scaffold->library === 'radium'): ?>
		<li>
			<?= $this->html->link('radium', '/radium');?>
		</li>
	<?php endif; ?>
	<li>
		<?= $this->html->link($this->scaffold->human, array('action' => 'index'));?>
	</li>
	<li class="active">
		<?= $this->title(sprintf('%s Schema', $this->scaffold->human)); ?>
	</li>
</ol>

<div class="header">
	<div class="col-md-12">
		<h3 class="header-title"><?= $this->title(); ?></h3>
		<!-- <p class="header-info">See a list of all <?= $this->scaffold->plural ?></p> -->
	</div>
</div>

<div class="main-content">
	<?= $this->scaffold->render('../radium/schema'); ?>
</div>
