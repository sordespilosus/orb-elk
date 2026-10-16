<template>
	<div class="base-input">
		<input
			v-bind="$attrs"
			v-on="listeners"
			v-mask="mask"
			:value="value"
			:type="type"
			class="base-input__input"
			:class="{
				'base-input__input--error' : isError,
				'base-input__input--smallest': smallest,
			}"/>
	</div>
</template>
<script>
export default {
	name: "BaseInput",
	inheritAttrs: false,
	props: {
		value: { type: null },
		isError: { type: Boolean, default: false },
		type: {
			type: String,
			default: "text",
			validator: type => {
				const validTypes = ["email", "text", "password", "number"];
				return validTypes.includes(type);
			},
		},
		mask: { type: [String, Object], default: () => ({}) },

		smallest: { type: Boolean },
	},
	computed: {
		listeners() {
			return {
				...this.$listeners,
				input: e => this.$emit("input", e.target.value),
				// в тех случаях, когда на поле стоит маска, генерируем
				// дополнительное событие, чтобы запустить обработку валидатором,
				// т.к. маска глушит input'ы
				// (GRPERM-3045)
				change: e => this.mask && this.$emit("input", e.target.value),
			};
		},
	},
};
</script>
<style lang="scss">
@import "~@/sass/variables";

.base-input {
	&__input {
		width: 100%;
		padding: 15px 12.5px;
		border: none;
		box-shadow: 0 2px 7px $shadow-color;
		border-radius: 10px;

		&::placeholder {
			font-weight: 500;
		}

		font-weight: 500;
		color: #0c2248;
		font-size: 18px;
		line-height: 20px;

		outline: none;

		&:disabled {
			opacity: 0.4;
			pointer-events: none;

			&:hover,
			&:focus {
				border-color: #d3d8db;
			}
		}

		&:read-only {
			pointer-events: none;

			&:hover,
			&:focus {
				border-color: #d3d8db;
			}
		}

		&--smallest {
			padding: 7px 15px;
			font-size: 16px;
			font-weight: 500;
		}

		&--error {
			background-color: #fff6f6;
			border-color: #ff5555;

			&:hover,
			&:focus {
				border-color: #ff5555;
			}
		}
	}
}
</style>
