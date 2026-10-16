<template>
  <div class="admin-sidebar">
    <router-link
    v-for="item in items"
    :to="item.to"
    :key="item.id"
    active-class="admin-sidebar__item_active"
    class="admin-sidebar__item">
      {{item.name}}
    </router-link>
  </div>
</template>
<script>
export default {
  name: 'AdminSidebar',
  props: {
    items: { type: Array, required: true }
  }
};
</script>
<style lang="scss">
.admin-sidebar {
  &__item {
    display: block;

    position: relative;

		&:nth-child(1) {
			border-radius: 10px 10px 0 0;
		}

		&:nth-last-child(1) {
			border-radius: 0 0 10px 10px;
		}

		padding: 15px;

    font-size: 13px;
    line-height: 20px;
    font-weight: bold;
    text-transform: uppercase;

    color: #3d3d3d;
    background-color: #fff;

    cursor: pointer;

    &:hover {
      background-color: #e4e9eb;
    }

    & + &::after {
      content: '';
      position: absolute;
      left: 20px;
      right: 20px;
      top: 0;
      height: 1px;
      background-color: #e4e9eb;
    }

    &_active,
    &_active:hover {
      color: #fff;
      background-color: #32ba91;
    }

    &_active::after {
      display: none;
    }
  }
}
</style>

