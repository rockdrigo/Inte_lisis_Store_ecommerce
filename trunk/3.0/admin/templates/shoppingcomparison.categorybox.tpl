<td id="Category-{{ parentid|safe }}">
	<div class="CategoryBox ISSelect">
		<ul>
			{% set selectedname = 'FOOO' %}
			{% for category in categories %}
				{% if selectedid == category.id %}
					{% set selectedname = category.name %}
				{% endif %}
				<li {% if selectedid == category.id %} class="SelectedRow" {% endif %}>
					<label>
						<input {% if selectedid == category.id %} checked="checked" {% endif %}type="radio" name="category_{{ category.parent_id }}" id="category_{{ category.id }}" value="{{ category.id }}" {% if category.num_children == 0 %}class="CategoryLeaf"{% endif %} />
						<span class='category_name'>{{ category.name }}</span> {% if category.num_children > 0 %} > {% endif %}
					</label>
				</li>
			{% endfor %}
			<div style="display:none" type="hidden" class="selected_category_name">{{ selectedname }}</div>
		</ul>
	</div>
</td>
