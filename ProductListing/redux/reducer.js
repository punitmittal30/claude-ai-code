import { configureStore, createAsyncThunk, createSlice, current } from "@reduxjs/toolkit";
import { act } from "react";
import { requestCatalogInstance, requestCategoryInstance, requestContentInstance, requestSearchInstance } from "service";

/**
 * Default state object with initial values.
 */
const initialState = {
  loading: true,
  error: false,
  filters: {},
  selectedFilter: [],
  isVariantDrawer: false,
  variants: {},
  notifyProduct: {},
  frequentlyBought:[],
  featureClass:'',
  productAddedSource:'',
  showHeaderOnOfferpage:null,

};


const removeExceptional = (obj) => {
  const exception = ['sort_by', 'price', 'ratings']
  for(let key in obj) {
    if(!exception.includes(key)) {
      delete obj[key]
    }
  }
  return obj
}

const checkfacetValues = (state, filter, isRemove = false) => {
  if(state[filter.field] !== 'page' && state.hasOwnProperty('page')) {
    delete state['page']
  }
  switch(filter.field) {
    // case 'price':
    //   if((filter.min === filter.currentMin) && (filter.max === filter.currentMax)) {
    //     delete state[filter.field]
    //   } else {
    //     state[filter.field] = `${filter.currentMin}_${filter.currentMax}`
    //   }
    //   break;
    case 'price':
      if(isRemove) {
        delete state[filter.field]
        return state
      }
      state[filter.field] = filter.value
      break;
    case 'ratings':
      if(isRemove){
        delete state[filter.field]
        return state
      }
      state[filter.field] = filter.value
      break;
    case 'sort_by':
      state[filter.field] = filter.value
      break;
    case 'page':
      if(filter.value == 1) {
        delete state[filter.field]
        return state
      }
      state[filter.field] = filter.value
      break;
    default:
      /** Uncheck logic for the checkbox */
      if(isRemove && state[filter.field]) {
        if(state[filter.field].length === 1) {
          delete state[filter.field]
          return state
        }
        const removeExisting = state[filter.field].filter(val => val !== filter.value)
        state[filter.field] = removeExisting
        return state
      }
      /** add Existing value to the checkbox  */
      if(state.hasOwnProperty(filter.field)) {
        if(state[filter.field].indexOf(filter.value) === -1) {
          state[filter.field].push(filter.value)
        }
      } else {
        state = {...state, [filter.field]: [filter.value] }
      }        
  }
  return state
}

export const fetchCategoryDetails = createAsyncThunk(
  "category/fetchProductCategory",
  async (data, thunkAPI) => {
    try {
      return await requestCategoryInstance(data);
    } catch (error) {
      return thunkAPI.rejectWithValue({ error: error.response });
    }
  }
);

export const fetchSearchDetails = createAsyncThunk(
  "category/fetchProductSearch",
  async (data, thunkAPI) => {
    try {
      return await requestSearchInstance(data);
    } catch (error) {
      return thunkAPI.rejectWithValue({ error: error.response });
    }
  }
);

export const fetchCategoryTopDeals = createAsyncThunk(
    "category/fetchCategoryTopDeals",
    async (data, thunkAPI) => {
      try {
        return await requestContentInstance(data);
      } catch (error) {
        return thunkAPI.rejectWithValue({ error: error.response });
      }
    }
);

export const fetchShopByCategory = createAsyncThunk(
    "category/fetchShopByCategory",
    async (data, thunkAPI) => {
      try {
        return await requestCatalogInstance(data);
      } catch (error) {
        return thunkAPI.rejectWithValue({ error: error.response });
      }
    }
);

/**
 * Create a slice as a reducer containing actions.
 *
 * In this example actions are included in the slice. It is fine and can be
 * changed based on your needs.
 */
export const categorySlice = createSlice({
  name: "productListing",
  initialState,
  reducers: {
    setInitialLoad: (state, action) => {
      state.loading = action.payload
    },
    setNotifyProduct: (state, action) => {
      state.notifyProduct = action.payload
    },
    setProductOption: (state, action) => {
      state.variants = action.payload
    },
    setFacetsFromSearch: (state, action) => {
      state.filters = action.payload
    },
    setFilterValues: (state, action) => {
      const updatedFacets = checkfacetValues(state.filters, action.payload, action.payload.isRemove)
      state.filters = updatedFacets
    },
    setMobileFilters: (state, action) => {
      state.filters = removeExceptional(state.filters)
      const facets = state?.selectedFilter?.forEach((el, i) => {
        if(el.field !== 'ratings' && el.field !== 'sort_by') {
          state.filters = checkfacetValues(state.filters, el)
        }
      })
    },
    setAppliedFilter: (state, action) => {
      state.selectedFilter = action.payload
    },
    setSelectedFilter: (state, action) => {
      const data = action.payload
      if(data.field === 'price' || data.field === 'ratings') {
        const checkExisting = state.selectedFilter.filter(el => el.field !== data.field)
        state.selectedFilter = [...checkExisting, {...action.payload}]
        return
      }
      state.selectedFilter = [...state.selectedFilter, {...action.payload}]
    },
    removeSelectedFilter: (state, action) => {
      const removeSelected = state.selectedFilter.filter((facet) => facet.value !== action.payload.value)
      state.selectedFilter = removeSelected
    },
    setEmptyFilter: (state, action) => {
      state.selectedFilter = []
      state.filters = {}
    },
    setFrequentlyBought:(state,action)=>{
      state.frequentlyBought = action.payload
    },
   setFeatureClass:(state,action)=>{
    state.featureClass = action.payload
   },
   setProdAddedSource :(state,action)=>{
    state.productAddedSource = action.payload
   },
   setShowHeaderOnOfferPage:(state,action)=>{
    state.showHeaderOnOfferpage = action.payload
   }
  },
});

// A small helper of user state for `useSelector` function.
export const getProductState = (state) => state.product;
export const { 
  setFilterValues,
  setFacetsFromSearch,
  setSelectedFilter,
  removeSelectedFilter,
  setEmptyFilter,
  setMobileFilters,
  setAppliedFilter,
  setProductOption,
  setInitialLoad,
  setNotifyProduct,
  setFrequentlyBought,
  setFeatureClass,
  setProdAddedSource,
  setShowHeaderOnOfferPage
 } = categorySlice.actions

// Exports all actions
// export const { setUpi } = checkoutSlice.actions;

export default categorySlice.reducer;
