<template>
	<div class="radio-group">
		<div
			class="radio-group__inner"
			:class="`radio-group__inner_${direction}`">
			<base-radio
				v-for="radio in items"
				v-bind="$attrs"
				v-model="model"
				:key="radio.value"
				:input-value="radio.value"
				:label="radio.label"
				class="radio-group__radio"/>
		</div>
	</div>
</template>
<script>
import BaseRadio from "./BaseRadio";
import {cloneDeep} from "lodash";

export default {
	name: "RadioGroup",
	components: { BaseRadio },
	inheritAttrs: false,
	props: {
		value: { type: null, required: true },
		items: { type: Array, requited: true },
		direction: { type: String, default: "row" },
	},

	computed: {
		model: {
			get() {
				return this.value;
			},

			set(value) {
				this.$emit("input", value);
			},
		},
	},
};
</script>
<style lang="scss">
.radio-group {
	&__inner {
		&_column {
			> * + * {
				margin-top: 10px;
			}
		}

		&_row {
			display: flex;

			> * {
				flex: 0 0 auto;
			}

			> * + * {
				margin-left: 20px;
			}
		}
	}
}
</style>