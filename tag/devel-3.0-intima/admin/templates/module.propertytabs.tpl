<tr>
	<td colspan="2">
		<input type="hidden" id="currentTab{{ ModuleId|safe }}" name="currentTab{{ ModuleId|safe }}" value="{{ CurrentModuleTabId|safe }}" />
		<ul id="tabnav">
			{{ ModuleTabs|safe }}
		</ul>
	</td>
</tr>