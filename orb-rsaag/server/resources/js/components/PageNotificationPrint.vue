<template>
  <div class="page-notification-print">
    <table class="page-notification-print__table">
      <thead>
      <tr>
        <th class="page-notification-print__subheader" colspan="2">
          <div class="page-notification-print__header">
          <img
          src="~@/images/gov.png"
          alt="Региональный сервис аутентификации и авторизации гражданина Оренбургской области"
          height="60px"
          width="auto"
          class="page-notification-print__logo"/>
          <div v-html="`Заявление №${info.service_number} от ${info.reg_service_date}<br>${info.service_name}`"/>
          </div>
        </th>
      </tr>
      </thead>
      <tbody>
        <tr>
          <td class="page-notification-print__subheader" colspan="2"
          v-text="'Данные о заявителе'"/>
        </tr>
        <tr>
          <td
          v-text="'Тип заявителя'"/>
          <td
          v-text="declarant"/>
        </tr>
        <tr>
          <td
          v-text="'ФИО'"/>
          <td
          v-text="getFullName('declarants.private_persons[0]')"/>
        </tr>
        <tr>
          <td
          v-text="'СНИЛС'"/>
          <td
          v-text="getProperty('declarants.private_persons[0].snils')"/>
        </tr>
        <tr>
          <td
          v-text="'Дата рождения'"/>
          <td
          v-text="getProperty('declarants.private_persons[0].birth_date')"/>
        </tr>
        <tr>
          <td
          v-text="'Адрес места жительства'"/>
          <td
          v-text="getProperty('declarants.private_persons[0].actual_address.value')"/>
        </tr>
        <tr>
          <td
          v-text="'Электронная почта'"/>
          <td
          v-text="getObjectField(getObjectFromArr('declarants.private_persons[0].contacts', 'kind', 'EMAIL'), 'value')"/>
        </tr>
        <tr>
          <td
          v-text="'Телефон'"/>
          <td
          v-text="getObjectField(getObjectFromArr('declarants.private_persons[0].contacts', 'kind', 'MOBILE'), 'value')"/>
        </tr>
        <tr>
          <td class="page-notification-print__subheader"  colspan="2"
          v-text="'Документ, удостоверяющий личность'"/>
        </tr>
        <tr>
          <td
          v-text="'Вид документа'"/>
          <td
          v-text="getPersonalityDoc('declarants.private_persons[0].documents', false)"/>
        </tr>
        <tr>
          <td
          v-text="'Серия, номер'"/>
          <td
          v-text="getPersonalityNumber(getPersonalityDoc('declarants.private_persons[0].documents'))"/>
        </tr>
        <tr>
          <td
          v-text="'Кем выдан'"/>
          <td
          v-text="getObjectField(getPersonalityDoc('declarants.private_persons[0].documents'), 'issuer_name')"/>
        </tr>
        <tr>
          <td
          v-text="'Дата выдачи'"/>
          <td
          v-text="getObjectField(getPersonalityDoc('declarants.private_persons[0].documents'), 'issue_date')"/>
        </tr>
        <tr>
          <td class="page-notification-print__subheader page-notification-print__delimiter" colspan="2"
          v-text="'Данные о ребенке'"/>
        </tr>
        <tr>
          <td
          v-text="'ФИО'"/>
          <td
          v-text="getFullName('others.private_persons[0]')"/>
        </tr>
        <tr>
          <td
          v-text="'СНИЛС'"/>
          <td
          v-text="getProperty('others.private_persons[0].snils')"/>
        </tr>
        <tr>
          <td
          v-text="'Дата рождения'"/>
          <td
          v-text="getProperty('others.private_persons[0].birth_date')"/>
        </tr>
        <tr>
          <td
          v-text="'Адрес места жительства'"/>
          <td
          v-text="getAddress('others.private_persons[0].registration_address')"/>
        </tr>
        <tr>
          <td
          v-text="'Копия свидетельства о регистрации'"/>
          <td>
            <a
            v-for="item in getObjectField(getObjectFromArr('others.private_persons[0].documents', 'document_code', '000144'), 'files')"
            target="_blank"
            v-text="getObjectField(item, 'name')"
            :href="`/my_notifications/file/${getObjectField(item, 'store_id')}?store=${getObjectField(item, 'store_name')}`"
            class="link notification-modal__history-section-link"/>
          </td>
        </tr>
        <tr>
          <td class="page-notification-print__subheader" colspan="2"
          v-text="'Документ, удостоверяющий личность'"/>
        </tr>
        <tr>
          <td
          v-text="'Вид документа'"/>
          <td
          v-text="getPersonalityDoc('others.private_persons[0].documents', false)"/>
        </tr>
        <tr>
          <td
          v-text="'Серия, номер'"/>
          <td
          v-text="getPersonalityNumber(getPersonalityDoc('others.private_persons[0].documents'))"/>
        </tr>
        <tr>
          <td
          v-text="'Кем выдан'"/>
          <td
          v-text="getObjectField(getPersonalityDoc('others.private_persons[0].documents'), 'issuer_name')"/>
        </tr>
        <tr>
          <td
          v-text="'Дата выдачи'"/>
          <td
          v-text="getObjectField(getPersonalityDoc('others.private_persons[0].documents'), 'issue_date')"/>
        </tr>
        <tr>
          <td
          v-text="'Образовательное учреждение'"/>
          <td
          v-text="getProperty('application_info.dest_department.full_name')"/>
        </tr>
        <tr>
          <td
          v-text="'Подтверждение согласия'"/>
          <td class="wysiwyg">
            <ul>
              <li v-text="'Подтверждаю, что вся представленная информация является достоверной и точной;'"/>
              <li v-text="'Несу ответственность в соответствии с действующим законодательством Российской Федерации за предоставление заведомо ложных или неполных сведений;'" />
              <li v-text="'С уставом учреждения и лицензией на осуществление образовательной деятельности, свидетельством о государственной аккредитации учреждения, образовательными программами и документами, регламентирующими организацию и осуществление образовательной деятельности, правами и обязанностями обучающихся ознакомлен(-а);'" />
              <li v-text="'Подтверждаю согласие на обработку представленных в настоящем заявлении персональных данных моих и ребенка, то есть их сбор, систематизацию, накопление, хранение, уточнение (обновление, изменение), использование, распространение (в том числе передачу), обезличивание, блокирование, уничтожение в целях получения муниципальной услуги «Прием на обучение по образовательным программам начального общего, основного общего и среднего общего образования»;'"/>
              <li v-text="'Со сроками оказания услуги ознакомлен.'"/>
            </ul>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
  import { get } from 'lodash';

  export default {
    name: "PageNotificationPrint",

    props: {
      content: { type: Object },
      info: { type: Object }
    },

    data: () => ({
      data: {},

    }),

    methods: {
      setAppLayout(value) {
        this.$store.commit('setData', { path: 'isHeaderHidden', value });
        this.$store.commit('setData', { path: 'isFooterHidden', value });
      },

      getProperty(path) {
        return get(this.content, path, null);
      },

      getObjectFromArr(arrayPath, mark, matches){
        const arr = this.getProperty(arrayPath);
        if(!arr)
          return null;
        return arr.find(i => i[mark] == matches);
      },

      getObjectField(obj, field){
        if(!obj)
          return null;
        return obj[field] ? obj[field] : null;
      },

      getFullName(path){
        const person = this.getProperty(path);
        return `${this.getObjectField(person, 'last_name')} ${this.getObjectField(person, 'first_name')} ${this.getObjectField(person, 'middle_name')}`;
      },

      getPersonalityNumber(document){
        return `${this.getObjectField(document, 'series')} ${this.getObjectField(document, 'number')}`;
      },

      getPersonalityDoc(path, returnObject = true){
        const types = {'000001': 'Паспорт гражданина РФ', '000146': 'Иной документ',
          '000002': 'Свидетельство о рождении', '000016': 'Свидетельство о рождении, выданное уполномоченным органом иностранного государства',
          '000145': 'Иной документ, подтверждающий родство заявителя'};

        const documents = this.getProperty(path);
        let ret = null;

        for (let el of documents){
          if(types[el.document_code]) {
            ret = returnObject ? el : types[el.document_code];
            break;
          }
        }
        return ret;
      },

      getAddress(path){
          const address = this.getProperty(path);
          const strAddress = `${address.city ? 'г. ' + address.city + ', ' : ''}${address.settlement ? address.settlement + ', ' : ''}
           ${address.street ? 'ул. ' + address.street + ', ' : ''}${address.house ? address.house + ', ' : ''}${address.block ? 'строение ' + address.block + ', ' : ''}
           ${address.housing ? 'блок ' + address.housing + ', ' : ''}${address.flat ? 'кв. ' + address.flat + ', ' : ''}`;
          return strAddress.substring(0, strAddress.length - 2);
      }
    },

    computed: {
      declarant(){
        if(this.getProperty('others.private_persons[0].relation_kind'))
          return this.getProperty('others.private_persons[0].relation_kind') == 'CHILD' ? 'Родитель' : 'Законный представитель';
        else
          return null;
      },
    },

    created() {
      this.setAppLayout(true);
      if (this.$route.query.print) window.print();
    }
  }
</script>

<style lang="scss">
.page-notification-print {

  background-color: #fff;
  margin-top: -90px;
  margin-bottom: -70px;

  &__table {
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    border: 1px solid black;
    border-collapse: collapse;

    td {
      padding: 5px;
      border: 1px solid black;
      &:first-child {
        width: 35%;
      }
    }
  }

  &__logo {
    flex: 0 0 auto;
    display: block;
    width: auto;
    height: 60px;
  }

  &__header {
    display: flex;
    align-items: center;

    > * + * {
      margin-left: 10px;
      margin-top: 10px;
      text-align: left;
      line-height: 18px;
    }
  }

  &__subheader {
    text-align: center;
    padding: 5px;
    font-weight: bold;
    border: 1px solid black;
  }
}
</style>
