<template>
  <div class="ff-menu-item__form">
    <h3 class="grey--text font-weight-bold">
      Add menu new item
    </h3>
    <v-progress-linear
      v-if="isLoading"
      indeterminate
    />
    <v-layout>
      <v-flex
        :class="!withoutServings ? 'xs8': 'xs10'"
        px-2
      >
        <div class="mb-2 text-uppercase grey--text font-weight-bold">
          Item Title
        </div>
        <v-text-field
          v-model="title"
          v-validate="validationRules.title"
          :error-messages="errors.collect('title')"
          data-vv-name="title"
          background-color="white"
          placeholder="Enter menu item title"
          single-line
          outline
        />
      </v-flex>
      <v-flex
        v-if="!withoutServings"
        xs2
        px-2
      >
        <div class="mb-2 text-uppercase grey--text font-weight-bold">
          servings
        </div>
        <v-text-field
          v-model="servings"
          v-validate="validationRules.servings"
          background-color="white"
          single-line
          outline
        />
      </v-flex>
      <v-flex
        xs2
        px-2
      >
        <div class="mb-2 text-uppercase grey--text font-weight-bold">
          Cost
        </div>
        <v-text-field
          v-model="cost"
          v-validate="validationRules.cost"
          :error-messages="errors.collect('cost')"
          data-vv-name="cost"
          background-color="white"
          single-line
          outline
        />
      </v-flex>
    </v-layout>
    <v-layout>
      <v-flex
        xs12
        px-2
      >
        <div class="text-uppercase grey--text font-weight-bold">
          Item description
        </div>
        <v-textarea
          v-model="description"
          v-validate="validationRules.description"
          :error-messages="errors.collect('description')"
          data-vv-name="description"
          background-color="white"
          single-line
          outline
        />
      </v-flex>
    </v-layout>
    <div>
      <v-btn
        depressed
        color="grey"
        class="white--text"
        @click="onCancel"
      >
        Cancel
      </v-btn>
      <v-btn
        depressed
        :loading="isLoading"
        color="primary"
        @click="whenValid(save)"
      >
        Save
      </v-btn>
    </div>
  </div>
</template>

<script>
import MapValueKeysToData from '../../mixins/MapValueKeysToData'
import FieldMeta from '@freshinup/core-ui/src/mixins/FieldMeta'
import Validate from 'fresh-bus/components/mixins/Validate'

export const DEFAULT_MENU_ITEM = {
  uuid: '',
  title: '',
  description: '',
  servings: 0,
  cost: 0,
  store_uuid: ''
}

/**
 * Menu Item Component
 * @property {String} title
 * @property {String} description
 * @property {Number} servings
 * @property {Number} cost
 */
export default {
  mixins: [MapValueKeysToData, Validate, FieldMeta],
  props: {
    // overriding value prop from mixin to set default value
    value: { type: Object, default: () => DEFAULT_MENU_ITEM },
    isLoading: { type: Boolean, default: false },
    withoutServings: { type: Boolean, default: false }
  },
  data () {
    return {
      ...DEFAULT_MENU_ITEM
    }
  },
  methods: {
    onCancel () {
      this.$emit('cancel')
    }
  }
}
</script>

<style lang="styl" scoped>
  .ff-menu-item__form {
    background-color: rgba(160,169,186,0.1); /*rgba(#a0a9ba, .10)*/;
    padding: 2rem;
  }

  .ff-menu-item__form h3 {
    color: #a0a9ba;
    margin-bottom: 1rem;
  }

  /deep/ .v-text-field--outline > .v-input__control > .v-input__slot {
    background: white!important;
  }
</style>
