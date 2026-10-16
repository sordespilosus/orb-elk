<template>
  <div class="spinner">
    <div class="spinner__content">
      <div class="spinner__icon">
        <div class="spinner__loader"/>
      </div>
      <div class="spinner__text">Идет загрузка данных</div>
    </div>

  </div>
</template>

<script>
export default {
  name: 'Spinner',
}
</script>

<style lang="scss">
.spinner {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;

  background-color: rgba(255,255,255,.8);
  z-index: 9999;

  &__content {
    position: fixed;
    left: 50%;
    top: 50%;
        
    display: flex;
    align-items: center;
    justify-content: center;

    padding: 20px 30px;

    transform: translate(-50%, -50%);

    border-radius: 60px;
    background-color: #fff;

    box-shadow: 0 3px 26px rgba(0, 0, 0, 0.16);
  }

  &__icon {
    position: relative;
    margin-right: 20px;
    height: 40px;
    width: 40px;
  }

  &__loader {
    position: absolute;
    top: 50%;
    left: 50%;
    z-index: 1000;

    width: 40px;
    height: 40px;

    transform: translate(-50%, -50%);

    &:before {
      content: '';

      position: absolute;
      top: 0%;
      left: 50%;
            
      width: 32px;
      height: 32px;

      margin-left: -20px;
      border: 4px solid rgba(0, 0, 0, 0.1);

      border-radius: 100%;
    }

    &:after {
      content: '';

      position: absolute;
      top: 0%;
      left: 50%;
            
      width: 32px;
      height: 32px;

      margin-left: -20px;
      border: 4px solid transparent;
      border-top-color: #30bf94;

      animation: loader 0.6s linear;
      animation-iteration-count: infinite;
      border-radius: 100%;

      box-shadow: 0px 0px 0px 1px transparent;
    }
  }

  &__text {
    font-size: 15px;
    font-weight: bold;
    white-space: nowrap;

    color: #d3d8db;
    text-transform: uppercase;
  }
}

@keyframes loader {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>

