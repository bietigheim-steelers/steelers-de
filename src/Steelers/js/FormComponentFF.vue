<template>
  <GroupElement name="ff" v-if="Date.now() < Date.parse('31 Jul 2024 23:59:59 GMT')"
    :conditions="[['ticket_category', '!=', null], ['ticket_category', '!=', 'familie1'], ['ticket_category', '!=', 'familie2'], ['ticket_category', '!=', 'familie3']]">
    <RadiogroupElement @change="onFFChange" name="family_and_friends" default="no" :items="[
      { value: 'werben', label: 'Ich will jemanden werben', description: '(zur Ablage im Smartphone)' },
      { value: 'geworben', label: 'Ich werde geworben' },
      { value: 'no', label: 'leider nicht' },
    ]" view="tabs">
    </RadiogroupElement>
    <TextElement name="ff_new_dk" rules="required" placeholder=" Name des neuen DK Inhabers"
      :conditions="[['ff.family_and_friends', '==', 'werben']]" />
    <TextElement name="ff_old_dk" rules="required" placeholder=" Name des werbenden DK Inhabers"
      :conditions="[['ff.family_and_friends', '==', 'geworben']]" />
    <template #label>
      <div class="text-lg leading-tight mt-2">Family & Friends:</div>
    </template>
  </GroupElement>

  <RadiogroupElement name="ticket_form" :conditions="[['ticket_category', '!=', null]]" rules="required"
    :items="ticketFormItems" view="blocks">
    <template #label>
      <div class="text-lg leading-tight mt-2">Dauerkartenform:</div>
    </template>
  </RadiogroupElement>
</template>

<script>
import { inject, computed } from 'vue'

export default {
  setup(props, context) {
    const form$ = inject('form$')
    
    const onFFChange = () => {
      form$.value.el$('ff.ff_new_dk').reset()
      form$.value.el$('ff.ff_old_dk').reset()
    }

    const ticket_type = computed(() => {
      return form$.value.data.ticket_type
    })
    const ticketFormItems = computed(() => {
      if (form$.value.data.family_and_friends == 'werben' || form$.value.data.family_and_friends == 'geworben') {
        return [
          { value: 'plastik', label: 'Klassische Plastikkarte' },
          { value: 'mobile_plastik', label: 'Klassische Plastikkarte + mobile Dauerkarte' },
        ]
      }
      return [
        { value: 'plastik', label: 'Klassische Plastikkarte' },
        { value: 'mobile_plastik', label: 'Klassische Plastikkarte + mobile Dauerkarte' },
        { value: 'mobile', label: 'Mobile Dauerkarte', description: '(zur Ablage im Smartphone)' },
      ]
    })


    return {
      ticket_type,
      onFFChange,
      ticketFormItems,
    }
  },
}
</script>