<div class="EndingItemReasonBox ISSelect" style="width:468px;height:134px;">
	<ul>
		{% for key in endReasons|keys %}
		<li>
			<label for="endReasons_{{ key|safe }}">
				<input type="radio" name="endReasons" id="endReasons_{{ key|safe }}" value="{{ key|safe }}" />
				{{ endReasons[key] }}
			</label>
		</li>
		{% endfor %}
	</ul>
</div>
