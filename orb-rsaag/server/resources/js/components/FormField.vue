<template>
	<div
		class="form-field"
		:class="{'form-field_required': isRequired}">
		<div class="form-field__header" v-if="label || $slots.toggle">
			<div class="form-field__label" v-if="label">{{ label }}</div>
			<div class="link form-field__toggle" v-if="$slots.toggle">
				<slot name="toggle"/>
			</div>
		</div>
		<div class="form-field__item">
			<slot/>
		</div>
		<div class="form-field__errors" v-if="errors">
			<div
				v-for="error in errors"
				:key="error"
				class="form-field__error">
				{{ error }}
			</div>
		</div>
	</div>
</template>
<script>
export default {
	name: "FormField",
	props: {
		label: { type: String },
		errors: { type: null },
		isRequired: { type: Boolean, default: false },
	},
};
</script>
<style lang="scss">
.form-field {
	&__header {
		display: flex;
		align-items: flex-start;
		justify-content: flex-end;

		margin-bottom: 10px;

		& > * + * {
			margin-left: 10px;
		}
	}

	&__label {
		flex: 1 1 auto;

		font-size: 18px;
		line-height: 15px;
		font-weight: 500;
		color: #0c2248;
	}

	&__toggle {
		flex: 0 1 auto;

		font-size: 13px;
		line-height: 15px;
		cursor: pointer;
	}

	&__errors {
		margin-top: 5px;
	}

	&__error {
		width: 100%;

		font-size: 10px;
		line-height: 15px;
		color: #ff5555;

		/*white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;*/
	}

	&_required {
		position: relative;

		.form-field__label::after {
			content: "*";
			position: absolute;
			left: auto;
			top: -3px;
			margin-left: 3px;
			color: #d3d8db;
		}
	}
}
</style>