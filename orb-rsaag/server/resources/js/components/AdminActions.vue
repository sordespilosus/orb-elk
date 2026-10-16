<template functional>
  <portal to="adminActions">
    <div class="admin-actions">
      <slot/>
    </div>
  </portal>
</template>

<style lang="scss">
.admin-actions {
  > * + * {
    margin-top: 10px;
  }
}
</style>