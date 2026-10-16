<template>
	<div
		class="field-dropdown"
		:class="modifiers">
		<multiselect
			v-model="model"
			v-on="listeners"
			@open="isActive = true"
			@close="isActive = false"
			v-bind="options"
			class="field-dropdown__inner">

			<icon
				name="dropdown"
				class="multiselect__caret"
				slot="caret"/>

			<template slot="noResult">Ничего не найдено</template>

		</multiselect>
	</div>
</template>

<script>
import Multiselect from "vue-multiselect";

export default {
	name: "FieldDropdown",

	components: { Multiselect },

	inheritAttrs: false,

	props: {
		data: { type: Array, required: true },

		value: { required: true },
		valueName: { type: String },
		labelName: { type: String },

		isError: { type: Boolean },
	},

	data: () => ({
		isActive: false,
	}),

	computed: {
		listeners() {
			const { input, ...listeners } = this.$listeners;
			return listeners;
		},

		modifiers() {
			return {
				"field-dropdown_active": this.isActive,
				"field-dropdown_error": this.isError,
				"field-dropdown_disabled":
					"disabled" in this.$attrs && this.$attrs.disabled !== false,
				"field-dropdown_readonly":
					"readonly" in this.$attrs && this.$attrs.readonly !== false,
			};
		},

		options() {
			const isMultiple = "multiple" in this.$attrs;
			const defaults = {
				options: this.data,
				showLabels: false,
				optionHeight: 60,
				placeholder: "Выберите вариант",
				allowEmpty: isMultiple,
				searchable: false,
			};
			if (this.labelName) {
				defaults.label = this.labelName;
			}
			return {
				...defaults,
				...this.$attrs,
			};
		},

		model: {
			get() {
				let { valueName, value, data } = this;
				const isMultiple = "multiple" in this.$attrs;
				const isGroup = "group-values" in this.$attrs;
				const extractValue = val => (valueName ? val[valueName] : val);

				if (isGroup) {
					const key = this.$attrs["group-values"];
					data = data.reduce((result, d) => {
						result = result.concat(d[key]);
						return result;
					}, []);
				}
				return isMultiple
					? data.filter(item => value && value.includes(extractValue(item)))
					: data.find(item => extractValue(item) === value);
			},

			set(value) {
				const { valueName } = this;
				const isMultiple = "multiple" in this.$attrs;
				const extractValue = val => (valueName ? val[valueName] : val);

				const result = isMultiple
					? value.map(extractValue)
					: extractValue(value);

				this.$emit("input", result);
			},
		},
	},
};
</script>
<style lang="scss">
@import "~@/sass/variables";

.field-dropdown {
	// Custom Properties:
	// ==================
	//   --accent-color
	//   --field-dropdown-error-color
	//   --field-dropdown-disabled-color

	//   --field-dropdown-border-color
	//   --field-dropdown-border-radius
	//   --field-dropdown-height
	//   --field-dropdown-padding-left
	//   --field-dropdown-padding-right

	--field-dropdown-border-radius: 10px;
	//box-shadow: 0 2px 7px rgba(0, 0, 0, .2);

	//Computed properties. Назначать нельзя. Рассчитываются исходя из динамических свойств.
	--field-dropdown-computed-height: var(--field-dropdown-height, 50px);
	--field-dropdown-computed-padding: calc(
		(var(--field-dropdown-computed-height) - 35px) / 2
	); //35px: (line-height + border-top + border-bottom + margin-top + margin-bottom
	--field-dropdown-computed-padding-left: calc(
		var(--field-dropdown-padding-left, 12.5px) - 5px
	); //5px: margin-top
	--field-dropdown-computed-padding-right: var(
			--field-dropdown-padding-right,
			var(--field-dropdown-computed-height)
	);
	--field-dropdown-computed-caret-padding: calc(
		(var(--field-dropdown-computed-height) - 20px) / 2
	); //20px: width (ширина иконки)

	--field-dropdown-computed-accent-color: var(--accent-color, #{$color-accent});

	.multiselect {
		display: block;
		position: relative;
		width: 100%;
		min-height: var(--field-dropdown-computed-height);
		box-shadow: 0 2px 7px $shadow-color;
		border-radius: 10px;

		color: #7b898d;

		touch-action: manipulation;

		&__spinner {
			position: absolute;
			right: 0;
			top: 0;

			width: var(--field-dropdown-computed-height);
			height: var(--field-dropdown-computed-height);
		}

		&__spinner::before {
			content: "";

			position: absolute;
			top: calc(50% - 8px);
			left: calc(50% - 8px);

			width: 16px;
			height: 16px;
			border-radius: 100%;
			border-color: var(--field-dropdown-computed-accent-color) transparent transparent;
			border-style: solid;
			border-width: 2px;
			box-shadow: 0 0 0 1px transparent;

			background-color: #fff;

			animation: spinning 2.4s cubic-bezier(0.41, 0.26, 0.2, 0.62);
			animation-iteration-count: infinite;
		}

		&__spinner::after {
			content: "";

			position: absolute;
			top: calc(50% - 8px);
			left: calc(50% - 8px);

			width: 16px;
			height: 16px;
			border-radius: 100%;
			border-color: var(--field-dropdown-computed-accent-color) transparent transparent;
			border-style: solid;
			border-width: 2px;
			box-shadow: 0 0 0 1px transparent;

			animation: spinning 2.4s cubic-bezier(0.51, 0.09, 0.21, 0.8);
			animation-iteration-count: infinite;
		}

		&__input {
			position: relative;
			display: block;

			width: 100%;
			min-height: 23px;
			margin: 5px;
			padding: 0;

			border: none;

			font-family: inherit;
			font-size: 18px;
			line-height: 23px;
			background-color: #fff;

			touch-action: manipulation;

			&::placeholder {
				color: #7b898d;
			}

			&:focus {
				outline: none;
			}
		}

		&__single {
			position: relative;
			display: block;

			min-height: 23px;

			font-size: 18px;
			line-height: 23px;

			margin: 5px;

			touch-action: manipulation;
		}

		&__tags {
			min-height: var(--field-dropdown-computed-height);
			padding: var(--field-dropdown-computed-padding);
			padding-left: var(
					--field-dropdown-computed-padding-left,
					--field-dropdown-computed-padding
			);
			padding-right: var(--field-dropdown-computed-padding-right);
			//border: 1px solid var(--field-dropdown-border-color, #d3d8db);

			border-radius: var(--field-dropdown-border-radius, 2px);
			background-color: #fff;

			transition: border 0.2s;

			&:hover {
				border-color: var(--field-dropdown-computed-accent-color);
			}
		}

		&__tags-wrap {
			display: flex;
			align-items: flex-start;
			flex-wrap: wrap;
		}

		&__tag {
			position: relative;

			flex: 0 1 auto;

			max-width: 100%;
			padding-left: 10px;
			padding-right: 25px;
			margin: 5px;

			border-radius: 5px;

			font-size: 15px;
			line-height: 23px;

			color: #fff;

			background-color: var(--field-dropdown-computed-accent-color);

			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		&__tag ~ &__input,
		&__tag ~ &__single {
			width: auto;
		}

		&__tag-icon {
			flex: 0 0 auto;

			position: absolute;
			top: 0;
			right: 0;

			width: 20px;

			font-weight: bold;
			text-align: center;

			outline: none;

			transition: all 0.2s ease;
			cursor: pointer;

			&::after {
				content: "×";
				color: #fff;
				font-style: normal;
			}

			&:focus,
			&:hover {
				background-color: var(--field-dropdown-computed-accent-color);
				filter: brightness(90%);
			}
		}

		&__select {
			position: absolute;
			top: 0;
			right: 0;
			width: var(--field-dropdown-computed-height);
			height: var(--field-dropdown-computed-height);

			transition: transform 0.2s ease;

			cursor: pointer;

			&::before {
				content: "";
				position: absolute;

				left: calc(50% - 5px);
				top: calc(50% - 2.5px);

				color: #999;

				border: 5px solid transparent;
				border-top-color: #999;
			}
		}

		&__caret {
			position: absolute;
			right: 0;
			top: 0;

			width: var(--field-dropdown-computed-height);
			height: var(--field-dropdown-computed-height);

			padding: var(--field-dropdown-computed-caret-padding);

			transition: transform 0.2s ease;
		}

		&__placeholder {
			display: block;
			margin: 6px 5px;

			font-size: 18px;
			line-height: 23px;
			color: #adadad;
		}

		&__content-wrapper {
			position: absolute;
			z-index: 50;

			width: 100%;
			max-height: 240px;
			border: 1px solid #e8e8e8;
			border-top: none;
			border-bottom-left-radius: 5px;
			border-bottom-right-radius: 5px;

			background-color: #fff;

			overflow: auto;
			-webkit-overflow-scrolling: touch;
		}

		&__content {
			display: inline-block;
			min-width: 100%;
			vertical-align: top;

			&::webkit-scrollbar {
				display: none;
			}
		}

		&__element {
			display: block;
		}

		&__option {
			display: flex;
			align-items: flex-start;

			min-height: var(--field-dropdown-computed-height);
			padding: calc(
				(var(--field-dropdown-computed-height) - 20px) / 2
			); //20px - option-line-height

			font-size: 18px;
			line-height: 20px;

			cursor: pointer;

			> span {
				flex: 1 1 auto;
			}

			&::after {
				flex: 0 0 auto;

				font-size: 13px;
			}

			&--highlight {
				background-color: rgba(#f0f3f5, 0.7);
				outline: none;
				color: #7b898d;
			}

			&--highlight::after {
				content: attr(data-select);
				background-color: transparent;
				color: #7b898d;
			}

			&--selected {
				color: #2a3e4c;
				font-weight: bold;
			}

			&--selected::after {
				content: attr(data-selected);
				color: #7b898d;
				font-weight: normal;
			}

			&--selected.multiselect__option--highlight::after {
				content: attr(data-deselect);
			}

			&--disabled {
				pointer-events: none;
			}

			&--disabled.multiselect-options--highlight {
				background-color: transparent;
			}

			&--group {
				background-color: #7b898d;
				color: #fff;
				font-weight: bold;
			}

			&--group.multiselect__option--highlight::after {
				background-color: inherit;
				color: inherit;
			}

			&--group-selected.multiselect__option--highlight::after {
				content: attr(data-deselect);
				background-color: inherit;
				color: inherit;
			}
		}

		&__strong {
			display: block;
			margin: 5px;
			font-size: 18px;
			line-height: 23px;
		}

		.multiselect__loading-enter-active,
		.multiselect__loading-leave-active {
			transition: opacity 0.4s ease-in-out;
			opacity: 1;
		}

		.multiselect__loading-enter,
		.multiselect__loading-leave-active {
			opacity: 0;
		}

		.multiselect-enter-active,
		.multiselect-leave-active {
			transition: all 0.15s ease;
		}

		.multiselect-enter,
		.multiselect-leave-active {
			opacity: 0;
		}

		@keyframes spinning {
			from {
				transform: rotate(0);
			}
			to {
				transform: rotate(2turn);
			}
		}

		> * {
			box-sizing: border-box;
		}

		&:focus {
			outline: none;
		}

		&--active .multiselect__tags {
			border-color: var(--field-dropdown-computed-accent-color);
		}

		&--active:not(.multiselect--above) .multiselect__input,
		&--active:not(.multiselect--above) .multiselect__tags {
			border-bottom-left-radius: 0;
			border-bottom-right-radius: 0;
		}

		&--active .multiselecr__select,
		&--active .multiselect__caret {
			transform: rotateZ(180deg);
		}

		&--active .multiselect__placeholder {
			display: none;
		}

		&--above.multiselect--active .multiselect__input,
		&--above.multiselect--active .multiselect__tags {
			border-top-left-radius: 0;
			border-top-right-radius: 0;
		}

		&--above .multiselect__content-wrapper {
			bottom: 100%;
			border-bottom-left-radius: 0;
			border-bottom-right-radius: 0;
			border-top-left-radius: 5px;
			border-top-right-radius: 5px;
			border-bottom: none;
			border-top: 1px solid #e8e8e8;
		}
	}

	&_active {
		position: relative;
		z-index: 50;
	}

	&_error {
		--field-dropdown-computed-accent-color: var(
				--field-dropdown-error-color,
				#ff5555
		);
		--field-dropdown-border-color: var(--field-dropdown-computed-accent-color);
	}

	&_disabled {
		pointer-events: none;
		opacity: 0.6;

		.mutiselect__tags {
			background-color: var(--field-dropdown-disabled-color, #f0f3f5);
		}
	}

	&_readonly {
		pointer-events: none;
	}
}
</style>