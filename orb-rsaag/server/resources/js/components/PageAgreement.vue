<template>
  <div class="container wysiwyg page-agreement">
    <div class="page-agreement__about">
      <h2 v-text="'О сервисе'"/>
      <div v-html="description"/>
    </div>
    <div class="page-agreement__agreement">
      <h2 v-text="'Пользовательское соглашение'"/>
      <div v-html="agreement"/>
    </div>
  </div>
</template>
<script>
export default {
  name: 'PageAgreement',
  props: {
    description: { type: String, default: '' },
    agreement: { type: String, default: '' }
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.page-agreement {
	background: white;
	margin-bottom: 30px;
	border-radius: 10px;
	padding: 20px;

	@media (max-width: $break-lg) {
		width: calc(100% - 20px);
	}

	> * + * {
		margin-top: 20px;
	}
}
</style>
