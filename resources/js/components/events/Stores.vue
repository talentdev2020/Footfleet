<template>
  <v-layout>
    <v-card width="100%">
      <v-card-title class="justify-space-between px-4 py-2">
        <span class="grey--text font-weight-bold title text-uppercase">Event Fleet</span>
        <v-btn
          depressed
          color="primary"
          @click="addNew"
        >
          <v-icon
            left
          >
            add_circle
          </v-icon>
          Add new fleet member
        </v-btn>
      </v-card-title>
      <v-divider />
      <div>
        <v-layout
          class="pa-4"
          row
        >
          <v-flex>
            <store-filter
              :types="types"
              :statuses="statuses"
              @runFilter="filterStores"
            />
          </v-flex>
        </v-layout>
        <v-layout
          row
        >
          <v-flex>
            <store-list
              :stores="stores"
              :statuses="statuses"
              v-bind="$attrs"
              v-on="$listeners"
            />
          </v-flex>
        </v-layout>
      </div>
    </v-card>
  </v-layout>
</template>

<script>
import StoreList from './StoreList.vue'
import StoreFilter from './StoreFilter.vue'

export default {
  components: {
    StoreList,
    StoreFilter
  },
  props: {
    types: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] }
  },
  methods: {
    addNew () {
      this.$emit('manage-create')
      this.$emit('manage', 'create')
    },
    filterStores (params) {
      this.$emit('filter-stores', params)
    }
  }
}
</script>

<style scoped>
</style>
