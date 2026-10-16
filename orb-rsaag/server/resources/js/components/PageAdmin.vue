<template>
  <div class="container page-admin">
    <router-view name="adminSection" class="page-admin__main"/>
    <div class="page-admin__sidebar">
      <portal-target name="adminActions"/>
      <admin-sidebar :items="sidebarLinks"/>
    </div>
  </div>
</template>
<script>
import AdminSidebar from './AdminSidebar';

export default {
  name: 'PageAdmin',
  components: { AdminSidebar },
  props: {
    sidebarLinks: { type: Array }
  }
};
</script>

<style lang="scss">
.page-admin {
  display: flex;
  align-items: flex-start;

  padding-bottom: 20px;

  &__main {
    flex: 1 1 auto;
  }

  &__sidebar {
    position: sticky;
    top: 100px;

    flex: 0 0 280px;
    margin-left: 40px;

    > * + * {
      margin-top: 20px;
    }
  }
}
</style>
