import { mount } from '@vue/test-utils'
import createLocalVue from 'vue-cli-plugin-freshinup-ui/utils/testing/createLocalVue'
import { FIXTURE_REPORTABLES, FIXTURE_REPORTABLES_RESPONSE } from 'tests/__data__/reportables'
import Component from '~/pages/admin/financials/index.vue'
import createStore from 'tests/createStore'

describe('Admin Financial Reports Page', () => {
  let localVue, mock
  describe('Mount', () => {
    beforeEach(() => {
      const vue = createLocalVue({ validation: true })
      localVue = vue.localVue
      mock = vue.mock
    })
    afterEach(() => {
      mock.restore()
    })
    test('snapshot', done => {
      const vue = createLocalVue({ validation: true })
      localVue = vue.localVue
      mock = vue.mock
        .onGet('api/foodfleet/financial-reports', { params: { 'page[size]': 10, 'page[number]': 1 } })
        .reply(200, { data: FIXTURE_REPORTABLES })
        .onGet('api/foodfleet/financial-reports').reply(200, FIXTURE_REPORTABLES_RESPONSE)

      mock.onGet('api/foodfleet/devices')
        .reply(200, {})

      mock.onGet('api/foodfleet/payment/types')
        .reply(200, {})

      mock.onAny().reply(config => {
        console.warn('No mock match for ' + config.url, config)
        return [404, { message: 'No mock match for ' + config.url, data: config }]
      })

      const store = createStore({
        financialReports: {
          items: FIXTURE_REPORTABLES_RESPONSE
        }
      })

      const wrapper = mount(Component, {
        localVue: localVue,
        store
      })

      // Action: load the page data
      Component.beforeRouteEnterOrUpdate(wrapper.vm, null, null, async () => {
        await wrapper.vm.$nextTick()
        expect(wrapper.element).toMatchSnapshot()
        done()
      })
    })
  })
})
