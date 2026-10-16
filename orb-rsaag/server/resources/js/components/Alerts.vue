<template>
	<notifications v-bind="$attrs" v-on="$listeners" classes="alerts">
		<div
			class="alerts__inner"
			slot="body"
			slot-scope="{item, close}"
			:class="`alerts__inner_${item.type}`"
			@click="close">
			<icon name="success" class="alerts__icon" v-if="item.type === 'success'"/>
			<icon name="error" class="alerts__icon" v-if="item.type === 'error'"/>
			<div class="alerts__body">
				<div class="alerts__title" v-if="item.title">{{ item.title }}</div>
				<div class="alerts__text" v-if="item.text">{{ item.text }}</div>
			</div>
		</div>
	</notifications>
</template>
<script>
export default {
	name: "Alerts",
};
</script>
<style lang="scss">
.alerts {

	&__inner {
		position: relative;

		display: flex;

		padding: 20px;
		margin: 5px 0;

		width: 320px;

		border: 1px dotted transparent;
		border-left: 5px solid transparent;

		&_success {
			border-color: #99d300;
			color: #7fa800;
			background-color: #f9ffea;
		}

		&_error {
			background-color: #fff6f6;
			color: #9f3a38;
			border-color: #e0b4b4;
		}
	}

	&__icon {
		flex: 0 0 auto;
		margin-right: 10px;
	}

	&__body {

		> * + * {
			margin-top: 10px;
		}

	}

	&__title { //@todo: подумать о дизайне всплывашек с заголовоком!
		font-size: 13px;
		line-height: 20px;
	}

	&__text {
		font-size: 13px;
		line-height: 20px;
	}
}
</style>
