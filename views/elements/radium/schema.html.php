<h4>{{ model }}</h4>
<table class="table table-striped table-condensed">
	<colgroup>
		<col width="200" />
		<col width="130" />
		<col width="200" />
		<col width="*" />
	</colgroup>
	<thead>
		<tr>
			<th>Name</th>
			<th>Type</th>
			<th>Default</th>
			<th>Annotation</th>
		</tr>
	</thead>
	<tbody>
	{{#each fields}}
		<tr data-name="{{@key}}">
			<td class="name">
				<samp>{{@key}}</samp>
				{{#if this.required}}<b style="color:red">*</b>{{/if}}
			</td>
			<td class="type"><kbd>{{{ this.type }}}</kbd></td>
			<td class="default">
				{{#if this.default}}
					<code>{{ this.default }}</code>
				{{/if}}
			</td>
			<td class="default">
				{{#if this.annotation}}
					<small>{{ this.annotation }}</small>
				{{/if}}
			</td>
		</tr>
	{{else}}
		<tr>
			<td colspan="3"><h5>No schema found...</h5></td>
		</tr>
	{{/each}}
	</tbody>
</table>
