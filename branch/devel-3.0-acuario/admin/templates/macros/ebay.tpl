{% macro formatPriceOrPercent(amount, option, currency) %}
{% if option == 'percent' %}{{ amount }}{% else %}{{ amount|formatPrice(false, false, false, currency) }}{% endif %}
{% endmacro %}