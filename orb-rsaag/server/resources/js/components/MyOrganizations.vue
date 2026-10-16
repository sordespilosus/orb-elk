<template>
  <div class="my-organizations">
    <div class="my-organizations__inner">
        <div class="my-organizations__text">К Вашему профилю привязаны следующие юридические лица или индивидуальные предприниматели:</div>
        <div class="my-organizations__list">
            <div v-for="(organization, index) in organizationsModel()" :key="index">
                <base-list
                    :items="organization"
                    class="my-organizations__item"/>

                <a
                    v-if="organizations[index].need_inn"
                    :href="`/profile/bind_inn?org_id=${organizations[index].id}&${$store.getters.redirectURL}`"
                    class="link my-organizations__link">Подтвердить</a>
            </div>
        </div>

    </div>
  </div>
</template>

<script>
import BaseList from '@/js/components/BaseList';

export default {
  name: 'MyOrganizations',
  components: { BaseList },
  props: {
    organizations: { type: Array, required: true }
  },

  methods: {
      organizationsModel() {
          const orgTypeMatch = {
              '10.FED': 'Федеральный орган исполнительной власти',
              '11.REG': 'Орган исполнительной власти субъекта РФ',
              '12.LCL': 'Орган местного самоуправления',
              '13.PVD': 'Подведомственная организация федерального органа исполнительной власти',
              '20.GOV': 'Государственное учреждение',
              '21.MCL': 'Муниципальное учреждение',
              '30.FND': 'Государственный внебюджетный фонд',
              '31.PFN': 'Подведомственная организация государственного внебюджетного фонда',
              '40.MFC': 'Многофункциональный центр',
              '41.LEG': 'Законодательный (представительный) орган государственной власти субъекта Российской Федерации'
          };
          return this.organizations.map(organization => {
              const { short_name, full_name, phone, email, need_inn, org_info } = organization;
              let result = {
                  'Наименование': short_name || full_name,
                  'Телефон': phone,
                  'E-mail': email
              };
              if (org_info != null) {
                  result = {
                      ...result,
                      'Тип организации': orgTypeMatch[org_info.agencyType],
                      'Регион': org_info.agencyTerRange,
                  };
              }
              return {
                  ...result,
                  'Статус в системе': need_inn ? 'Ожидает подтверждения' : 'Подтверждена'
              }
          });
      }
  }
};
</script>

<style lang="scss">
.my-organizations {
  &__inner {

  }

    &__list {
        > * + * {
            margin-top: 20px;
        }
    }

  &__item {
      background-color: #fff;
      border-radius: 5px;
  }

  &__link {
      display: inline-block;
      margin-top: 5px;
  }

}
</style>
