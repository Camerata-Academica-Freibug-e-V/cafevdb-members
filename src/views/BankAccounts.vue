<!--
 - @copyright Copyright (c) 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <div class="page-container" :class="{ 'icon-loading': loading, loading }">
    <h2>
      {{ t(appId, 'Bank Accounts of {publicName} ({count})', { publicName: memberData.personalPublicName, count: showDeleted ? memberData.sepaBankAccounts.length : numActiveBankAccounts }) }}
    </h2>
    <NcCheckboxRadioSwitch v-if="haveDeleted" v-model="showDeleted">
      {{ t(appId, 'show deleted') }}
    </NcCheckboxRadioSwitch>
    <div v-if="memberData.sepaBankAccounts.length === 0">
      {{ t(appId, 'We have no information about your bank-accounts.') }}
    </div>
    <ul v-for="account in memberData.sepaBankAccounts"
        :key="account.sequence"
        class="sepa-bank-accounts-list"
    >
      <NcListItem v-if="showDeleted || !account.deleted"
                  :name="t(appId, 'IBAN')"
                  :bold="true"
                  class="bank-account"
      >
        <template #details>
          <span class="bank-account-iban">{{ account.iban }}</span>
        </template>
        <template #subname>
          <ul class="sepa-bank-account-details">
            <!-- <NcListItem :name="t(appId, 'BIC')" :details="account.bic" /> -->
            <NcListItem :name="t(appId, 'account holder')" :details="account.bankAccountOwner" />
            <NcListItem :name="t(appId, 'registered')" :details="formatDate(account.created)" />
            <NcListItem v-if="account.updated" :name="t(appId, 'modified')" :details="formatDate(account.updated)" />
            <NcListItem v-if="account.deleted" :name="t(appId, 'revoked')" :details="formatDate(account.deleted)" />
            <NcListItem v-if="(showDeleted && account.sepaDebitMandates) || (!showDeleted && account.numActiveDebitMandates > 0)"
                        :name="t(appId, 'Debit Mandates ({count})', { count: showDeleted ? account.sepaDebitMandates.length : account.numActiveDebitMandates })"
            >
              <template #subname>
                <ul v-for="mandate in account.sepaDebitMandates"
                    :key="mandate.sequence"
                    class="sepa-debit-mandates-list"
                >
                  <NcListItem v-if="showDeleted || !mandate.deleted"
                              :name="t(appId, 'reference')"
                              :details="mandate.mandateReference"
                  >
                    <template #subname>
                      <ul class="sepa-debit-mandate-details">
                        <NcListItem :name="t(appId, 'granted')" :details="formatDate(mandate.mandateDate)" />
                        <NcListItem v-if="mandate.lastUsedDate" :name="t(appId, 'last used')" :details="formatDate(mandate.lastUsedDate)" />
                        <NcListItem v-if="mandate.updated" :name="t(appId, 'modified')" :details="formatDate(mandate.updated)" />
                        <NcListItem v-if="mandate.deleted" :name="t(appId, 'revoked')" :details="formatDate(mandate.deleted)" />
                      </ul>
                    </template>
                  </NcListItem>
                </ul>
              </template>
            </NcListItem>
          </ul>
        </template>
      </NcListItem>
    </ul>
    <DebugInfo :debugData="memberData" />
  </div>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import {
  NcCheckboxRadioSwitch,
  NcListItem,
} from '@nextcloud/vue'
import {
  onBeforeMount,
  ref,
} from 'vue'
import DebugInfo from '../components/DebugInfo.vue'
import { appName as appId } from '../config.ts'
import { useMemberDataStore } from '../stores/memberData.ts'
import formatDate from '../util/formatDate.ts'

const viewName = 'BankAccounts'

const memberData = useMemberDataStore()
const loading = ref(true)
const showDeleted = ref(false)
const haveDeleted = ref(false)
const numActiveBankAccounts = ref(0)
const numDeletedBankAccounts = ref(0)

onBeforeMount(async () => {
  await memberData.initialize()

  if (memberData.initialized.loaded && !memberData.initialized[viewName]) {
    memberData.sepaBankAccounts.forEach((account, _index) => {
      // memberData.sepaBankAccounts[index].numDeletedDebitMandates = account.sepaDebitMandates.filter(mandate => !!account.deleted).length
      account.numDeletedDebitMandates = account.sepaDebitMandates.filter((mandate) => !!mandate.deleted).length
      account.numActiveDebitMandates = account.sepaDebitMandates.length - account.numDeletedDebitMandates
    })
    memberData.initialized[viewName] = true
  }

  if (memberData.initialized[viewName]) {
    // @todo: why are the following things not just "computed"
    numDeletedBankAccounts.value = memberData.sepaBankAccounts.filter((account) => !!account.deleted).length
    haveDeleted.value = numDeletedBankAccounts.value > 0
    numActiveBankAccounts.value = memberData.sepaBankAccounts.length - numDeletedBankAccounts.value
    memberData.sepaBankAccounts.forEach((account, _index) => {
      haveDeleted.value = haveDeleted.value || (account.numDeletedDebitMandates > 0)
    })
  }
  loading.value = false
})
</script>

<style lang="scss" scoped>
.page-container {
  padding-left:50px;
  padding-top:12px;
  min-height:100%;
  &.loading {
    width:100%;
    * {
      display:none;
    }
  }

  .sepa-bank-accounts-list {
    min-width:32rem;
    margin-right: 9px;
    padding-right: 4px;
  }

  :deep(.list-item__wrapper) {
    padding-right: 0;
    .list-item__anchor {
      height: fit-content;
    }
    .list-item {
      padding-top:2px;
      padding-bottom:2px;
      padding-right: 0;
    }
    .list-item-content {
      &__details {
        align-self: start;
        position: absolute;
        right: 0;
        .list-item-details__details {
          margin-right: 0 !important;
          padding-right: 0 !important;
        }
      }
    }
    &.bank-account {
      .bank-account-iban {
        font-weight: bold;
      }
      > .list-item > .list-item__anchor > .list-item-content {
        position: relative;
        margin-right: 9px;
      }
    }
  }
}
</style>
