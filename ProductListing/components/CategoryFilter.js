import React, { useEffect, useRef, useState } from 'react'
import CategoryFacet from 'modules/ProductListing/components/CategoryFacet'
import FormInput from 'shared/components/FormInput/FormInput'
import { ratings, dietaryPreference } from 'modules/ProductListing/components/constants'
import { StarIcon } from '@heroicons/react/outline'
import { useDispatch, useSelector } from 'react-redux'
import { removeSelectedFilter, setAppliedFilter, setEmptyFilter, setFilterValues, setSelectedFilter } from 'modules/ProductListing/redux/reducer'
import Nouislider from "nouislider-react";
import { filterSelectTracker } from 'analytics/trackers/navigate'
import { filterDataLayer } from 'analytics/datalayer/navigate'
import { filterFbq } from 'analytics/fbq/navigate'
import { generateSource } from 'analytics/helpers'
import { trackFilterSelect } from 'analytics/plugins/webEngage/navigate'
import { baseImgUrl } from 'utils/index'

const trackRatingFilter = (name, value) => {
  const trackingData = {
    name, source: generateSource()?.clickSource, value, featureSource: "normal_filters",
  };
  filterDataLayer(trackingData, { featureSource: "normal_filters" });
  filterFbq(trackingData)
  filterSelectTracker({ filter: trackingData });
  trackFilterSelect({ filter: trackingData });
}

const trackPriceFilter = (start, end) => {
  const trackingData = {
    name: "price", start, end, source: generateSource()?.clickSource, featureSource: "normal_filters",
  };
  filterDataLayer(trackingData, { featureSource: "normal_filters" });
  filterFbq(trackingData)
  filterSelectTracker({ filter: trackingData });
  trackFilterSelect({ filter: trackingData })
}

const CategoryFilter = ({filters, selectedFilter, subCategories, categoryTitle, isBrand}) => {
  const dispatch = useDispatch()
  const [updateFilter, setUpdateFilter] = useState([])
  const [updateCatFilter, setCategoryFilter] = useState([])
  const [rangeValue, setRangeValue] = useState({ min: 0, max: 2000 })
  const [startValue, setStartValue] = useState([0, 2000])
  const [maxRange, setMaxRange] = useState(1000)
  const selectedFilters = useSelector((state) => state.category.selectedFilter);

  const rangeFormat = {
    to: function(val) {
      return parseInt(val)
    },
    from: function(val) {
      return parseInt(val)
    }
  }

  useEffect(() => {
    // formatSelectedPrice()
    iterateFilters(JSON.parse(JSON.stringify(filters)))
  }, [selectedFilter, filters, maxRange])


  const iterateFilters = (facets) => {
    const facetSelected = facets?.filter((facet) => {
      if(facet.attribute_code === 'price') {
        facet.options.forEach((el) => {
          const label = el.label.split('-')
          el.label = `₹${label[0]} - ₹${label[1]}`
        })
        // const maxValue = facet.options[facet.options.length - 1].value.split('_')[1]
        // setMaxRange(Number(maxValue))
      }
      if(facet.attribute_code === 'is_hl_verified' || facet.attribute_code === 'is_hm_verified') {
        facet.options = facet.options.filter((el) => el.value == 1)
        if(facet.options && facet.options.length !== 0 && facet.attribute_code === 'is_hl_verified') {
          facet.options[0].icon = '/assets/images/h_tested_version1.svg'
        } else if(facet.options && facet.options.length !== 0 && facet.attribute_code === 'is_hm_verified') {
          facet.options[0].icon = `${baseImgUrl}/images/icons/h_metal_pdp.png`
        } else {
          facet.disabled = true
        }
      }
      if(isBrand) {
        if(subCategories?.length !== 0) {
          return (facet.attribute_code !== 'category_uid' && facet.attribute_code !== 'brand' && facet.attribute_code !== 'dietary_preference' && facet.attribute_code !== 'primary_l2_category' && !facet?.disabled)
        } else {
          return (facet.attribute_code !== 'category_uid' && facet.attribute_code !== 'brand' && facet.attribute_code !== 'dietary_preference' && !facet?.disabled)
        }
      } else {
        if(subCategories?.length !== 0) {
          return (facet.attribute_code !== 'category_uid' && facet.attribute_code !== 'dietary_preference' && facet.attribute_code !== 'primary_l2_category' && !facet?.disabled)
        } else {
          return (facet.attribute_code !== 'category_uid' && facet.attribute_code !== 'dietary_preference' && !facet?.disabled)
        }
      }
    }).map((filter, index) => {
      filter.options.forEach((el) => {
        if(typeof selectedFilter[filter.attribute_code] === 'object' && selectedFilter[filter.attribute_code]?.includes(el.value)) {
          console.log('selected cat is', selectedFilter[filter.attribute_code], el.value)
          el.selected = true
        } else if(typeof selectedFilter[filter.attribute_code] === 'string' && selectedFilter[filter.attribute_code] === el.value) {
          el.selected = true
        } else {
          el.selected = false
        }
      })
      return filter
    })
    const removeHVerified = facetSelected.filter((el) => el.disabled !== true)
    setUpdateFilter(removeHVerified)
    if(subCategories) {
      const cat = subCategories?.map((category) => {
        let filterCat = []
        if(selectedFilter['category_ids'] && selectedFilter['category_ids'].length !== 0) {
          filterCat = selectedFilter['category_ids'].map((el) => Number(el))
        }
        if(filterCat.indexOf(Number(category.id)) !== -1) {
            category.selected = true
        } else {
            category.selected = false
        }
        return category
    })
    // console.log('cat is', cat, facetSelected)
    setCategoryFilter(cat);

  }
  }
  
  const handleRating = (event, rating) => {
    dispatch(setFilterValues({ 
      field: 'ratings',
      value: event.target.value,
      label: rating.label
     }));
     dispatch(setSelectedFilter({
      field: 'ratings',
      value: event.target.value,
      label: `ratings: ${event.target.value}`
     }));
     trackRatingFilter('ratings', event.target.value)
  }
  // const handlePrice = (event, label) => {
  //   const selectedOption = {
  //     field: 'price',
  //     value: event.target.value,
  //     label: label
  //   }
  //   if(event.target.checked) {
  //       dispatch(setFilterValues(selectedOption))
  //       dispatch(setSelectedFilter(selectedOption))
  //   } else {
  //       dispatch(setFilterValues({...selectedOption, isRemove: true }))  
  //       dispatch(removeSelectedFilter(selectedOption))
  //   }
  // }

  const formatSelectedPrice = () => {
    if(selectedFilter['price']) {
      setStartValue(selectedFilter['price'].split('_'))
    } else {
      setStartValue([0, maxRange])
    }
  }

  const onTriggerUpdate = (event) => {
    setRangeValue({min: event[0], max: event[1]})
  }

  const onTriggerChange = (event) => {
    dispatch(setFilterValues({ 
      field: 'price',
      currentMin: event[0],
      currentMax: event[1],
      min:0,
      max: 2000
    }))
    dispatch(setSelectedFilter({ 
      field: 'price',
      value: `${event[0]}_${event[1]}`,
      label: `price: ${event[0]}_${event[1]}`
    }))
    trackPriceFilter(event[0], event[1])
  }

  const onClearFacets = () => {
    dispatch(setEmptyFilter())
  }

  const excludeQuery = () => {
    const filters = JSON.parse(JSON.stringify(selectedFilter))
    if(filters['q']) {
      delete filters['q']
    }
    if(filters['sort_by']) {
      delete filters['sort_by']
    }
    if(filters['page']) {
      delete filters['page']
    }
    const notAllowed = ['fbclid', 'utm_campaign', 'utm_content', 'utm_medium', 'utm_source']
    for(const filter in filters) {
      if(notAllowed.includes(filter)) {
        delete filters[filter]
      }
    }
    return filters
  }

  const handleCategoryFilter = (event, attr, category) => {
    // console.log('event', event.target.value)
    const _categories = [...updateCatFilter];
    const selectedOption = {
      field: attr,
      value: Number(event.target.value),
      label: category.name
    }
    if(event.target.checked) {
      dispatch(setFilterValues(selectedOption))
      dispatch(setSelectedFilter(selectedOption))
      selectedFilters = [...selectedFilters, selectedOption];
      _categories?.forEach(category => {
        if (event.target.value === category.id) {
          category.selected = true;
        }
      })
    } else {
      dispatch(setFilterValues({...selectedOption, isRemove: true }))
      dispatch(removeSelectedFilter(selectedOption))
      selectedFilters = selectedFilters.filter(e => e?.label !== selectedOption?.label);
      _categories?.forEach(category => {
        if (event.target.value === category.id) {
          category.selected = false;
        }
      });
    }
    
    const selectedCategories = _categories.filter(category => category.selected === true);
    if (selectedCategories.length === 0) return;
    // const selectedCategoriesLabel = selectedCategories.map(
    //   _category => subCategories.find(category => category.id === _category.id.toString()).name
    // );
    const trackingData = {
      name: "Narrow by category",
      source: generateSource()?.clickSource,
      group: selectedFilters && selectedFilters.length ? selectedFilters.map((selectedFilter) => { return selectedFilter?.label }) : undefined,
      featureSource: "normal_filters",
      action: event.target.checked ? "add" : "remove",
      value: `${selectedOption?.value}_${selectedOption?.field}`,
      label: `${selectedOption?.label}`,
    };
    filterSelectTracker({filter: trackingData});
    filterDataLayer(trackingData, { featureSource: "normal_filters" });
    trackFilterSelect({filter: trackingData})
  }

  return (
    <div className={`category-filter-container sticky`}>
      <div className='filter-title flex justify-between mb-3'>
        <p className='text-2xl text-gray-100 font-semibold'>Filters</p>
        {(Object.keys(excludeQuery()).length !== 0) ? <div className='flex items-center text-base font-semibold cursor-pointer pr-2' onClick={onClearFacets}>Clear All</div>: null }
      </div>
      {updateCatFilter?.length !== 0 && <div className={`category-filter-facets border rounded-lg mb-5`}>
        <div className='category-title text-xl text-gray-100 px-6 font-normal py-4 border-b-[1px]'>Narrow By Category</div>
        {categoryTitle && <p className='text-lg text-gray-100 font-semibold px-6 py-4'>{categoryTitle}</p>}
        <div className={`px-6 pr-4 max-h-[360px] overflow-auto`}>
            {updateCatFilter.map((category, i) => <div key={i} className={`pb-2 pt-1.5 border-b-[#E7E7E7] sm:border-b-0 sm:pt-0 sm:pb-4 flex items-center justify-between filter-category_${category.name}`}>
              <div className='truncate pr-1'>
                <FormInput type={'checkbox'} label={category.name} id={category.slug} value={category.id} checked={category?.selected} onChange={(e) => handleCategoryFilter(e, 'category_ids', category)}/>
              </div>
                {category?.count && <span className='facet-count text-[#8B8B95] font-normal relative top-[3px] text-sm sm:text-base'>{category?.count}</span>}
            </div>)}
        </div>
      </div> }
      {updateFilter.map((filter, index) => {
          return <CategoryFacet key={filter.attribute_code} facetData={filter} isExpand={(subCategories.length === 0) ? true : false} isSearch={true}  />
       })}
       {/* <CategoryFacet title={'Price'} isContent={true} isExpand={(subCategories.length === 0) ? true : false}> */}
          {/* <div className='pb-4 flex cursor-pointer'>
            <FormInput type={'checkbox'} value={'0_1000'} checked={selectedFilter['price'] === '0_1000'} labelStyle={`top-[-2px]`} label={'₹0 - ₹1000'} id={'0_1000'} onChange={(e) =>handlePrice(e, '₹0 - ₹1000')} />
          </div> */}
          {/* <div className='flex justify-between text-gray-100 text-base'>
            <span className='range-price-lower-value'>₹{rangeValue.min}</span>
            <span className='range-price-uppe-valuer'>₹{rangeValue.max}</span>
          </div> */}
          {/* <Nouislider range={{ min: 0, max: maxRange }} start={startValue} format={rangeFormat} connect onUpdate={(e) => onTriggerUpdate(e)}  onChange={(e) => onTriggerChange(e)} className={'price-slider-ui h-2 w-[90%] mb-6 mt-2'} /> */}
       {/* </CategoryFacet> */}
      {/* <CategoryFacet title={'Ratings'} isContent={true}>
          {ratings.map((rating) => <div key={rating.value} className='pb-4 flex cursor-pointer'>
            <FormInput type={'radio'} value={rating.value} id={rating.value} name={rating.name} checked={rating.value === selectedFilter['ratings']} labelStyle={'flex items-center'} onChange={(e) => handleRating(e, 'ratings', rating)}>
                {Array.from(Array(rating.label), (el, i) => <StarIcon key={i} className='h-6 w-6 pb-1.5' />)}
                <span className='relative bottom-[3px]'>& Above</span>
            </FormInput>
          </div>)}
      </CategoryFacet> */}
    </div>
  )
}

export default CategoryFilter