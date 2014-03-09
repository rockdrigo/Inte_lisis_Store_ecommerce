<td>
	<div id="EbayCat{{ parentId|safe }}" class="EbayCategoryBox ISSelect">
		<ul>
			{% for categoryId, category in categories %}
				<li>
					<label for="category_{{ categoryId }}">
						<input type="radio" name="category_{{ parentId }}" id="category_{{ categoryId }}" value="{{ categoryId }}" {% if category.is_leaf %}class="CategoryLeaf"{% endif %} />
						{{ category.name }}{% if category.is_leaf in [0] %}&nbsp;&gt;{% endif %}
					</label>
				</li>
			{% endfor %}
		</ul>
	</div>
</td>