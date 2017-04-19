/**
 * Class xlvoNumberRange
 * @type {{}}
 */
var xlvoNumberRange = {
	init: function (json) {
		var config = JSON.parse(json);
		var replacer = new RegExp('amp;', 'g');
		config.base_url = config.base_url.replace(replacer, '');
		this.config = config;
		this.ready = true;
		this.percentageSign = '';

	},
	config: {},
	base_url: '',
	run: function () {

		this.percentageSign = $('#percentage')[0].value === "1" ? '%' : '';

		var numberDisplay = $('#number-display');
		var oldText = numberDisplay.text();

		numberDisplay.text(oldText.concat(this.percentageSign));

		var slider = $("#slider").bootstrapSlider();
		var thisRef = this;

		slider.change(
			function (changedValues) {
				$('#number-display').text(String(changedValues.value.newValue).concat(thisRef.percentageSign));
		}).bind(thisRef);
	},
	/**
	 * @param button_id
	 * @param button_data
	 */
	handleButtonPress: function (button_id, button_data) {

	}
};
