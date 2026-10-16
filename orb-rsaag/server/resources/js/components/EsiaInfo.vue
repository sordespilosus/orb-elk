<template>
	<div class="esia-info">
		<div class="esia-info__inner">
			<div class="esia-info__text">Ваши данные из Госуслуг:</div>

			<base-list
				:show-nullable="false"
				:items="infoModel"
				class="esia-info__item"/>

			<template v-if="esiaInfo.passport">
				<div class="esia-info__text">Паспорт:</div>
				<base-list
					:show-nullable="false"
					:items="toPassportFormat(esiaInfo.passport)"
					class="esia-info__item"/>
			</template>

			<template v-if="esiaInfo.kids">
				<div class="esia-info__text">Дети:</div>
				<base-list
					:show-nullable="false"
					v-for="(kid, index) in esiaInfo.kids"
					:key="index"
					:items="toKidFormat(kid)"
					class="esia-info__item"/>
			</template>
		</div>
	</div>
</template>

<script>
import BaseList from "@/js/components/BaseList";

export default {
	name: "EsiaInfo",
	components: { BaseList },
	props: {
		esiaInfo: { type: Object, required: true },
	},

	computed: {
		infoModel() {
			return {
				surname: {
					key: "Фамилия",
					value: this.esiaInfo.surname,
				},
				name: {
					key: "Имя",
					value: this.esiaInfo.name,
				},
				patroname: {
					key: "Отчество",
					value: this.esiaInfo.patroname,
				},
				birthday: {
					key: "Дата рождения",
					value: this.esiaInfo.birthday,
				},
				email: {
					key: "Email",
					value: this.esiaInfo.email || "Данное поле пустое",
				},
				gender: {
					key: "Пол",
					value: this.esiaInfo.gender,
				},
				phone: {
					key: "Телефон",
					value: this.esiaInfo.phone,
				},
				snils: {
					key: "СНИЛС",
					value: this.esiaInfo.snils,
				},
				trusted: {
					key: "Учтёная запись",
					value: this.esiaInfo["trusted"],
				},
			};
		},
	},

	methods: {
		toKidFormat(kid) {
			return {
				surname: {
					key: "Фамилия",
					value: kid["lastName"],
				},
				name: {
					key: "Имя",
					value: kid["firstName"],
				},
				patroname: {
					key: "Отчество",
					value: kid["middleName"],
				},
				birthday: {
					key: "Дата рождения",
					value: kid["birthDate"],
				},
				gender: {
					key: "Пол",
					value: kid["gender"],
				},
			};
		},

		toPassportFormat(passport) {
			return {
				seriesAndNumber: {
					key: "Сeрия и номер",
					value: passport.number,
				},
				dateOfIssue: {
					key: "Дата выдачи",
					value: passport["issue_date"],
				},
				passport: {
					key: "Паспорт выдан",
					value: passport["issued_by"],
				},
				code: {
					key: "Код подразделения",
					value: passport["issue_id"],
				},
			};
		},
	},
};
</script>

<style lang="scss">
.esia-info {
	&__inner {
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
