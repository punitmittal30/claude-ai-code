import { ArrowLeftIcon, StarIcon } from '@heroicons/react/outline'
import { removeSelectedFilter, setEmptyFilter, setFilterValues, setMobileFilters, setSelectedFilter } from 'modules/ProductListing/redux/reducer'
import React, {useEffect, useState } from 'react'
import { useDispatch } from 'react-redux'
import Tabs from 'shared/components/Tabs/Tabs'
import CategoryFacet from 'modules/ProductListing/components/CategoryFacet'
import { ratings, staticFilter } from '../../constants'
import Nouislider from "nouislider-react";
import FormInput from 'shared/components/FormInput/FormInput'

const MobileFacets = ({ hideFilter, facets, selectedFilter, selectedChips, subCategories, isBrand }) => {
  const dispatch = useDispatch()
  const [activeTab, setTab] = useState(0)
  const [facetClone, setFacetClone] = useState([])
  const [rangeValue, setRangeValue] = useState({ min: 0, max: 2000 })
  const [startValue, setStartValue] = useState([0, 2000])
  const [maxRange, setMaxRange] = useState(1000)
  const [catFacet, setCatFacet] = useState([])

  const excludeFacets = (facets) => {
    const filteredData = facets.filter((facet) => {
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
        if(facet.options && facet.options.length !== 0) {
          if(facet.attribute_code === 'is_hl_verified') {
            facet.options[0].label = 'H Tested'
          } else {
            facet.options[0].label = 'H Metal Tested'
            facet.options[0].value = Number(facet.options[0].value)
            facet.options[0].unique_value = '1_is_hm_verfied'
          }
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
      const options = filter.options.forEach((el) => {
        if(el.unique_value === '1_is_hm_verified') {
          if(modifySingleArr(selectedChips)?.indexOf(Number(category.id)) !== -1) {
            el.selected = true
          } else {
            el.selected = false
          }
        }
        if(modifySingleArr(selectedChips)?.includes(el.value)) {
          el.selected = true
        } else {
          el.selected = false
        }
      })
      return filter
    })
    if(facets && facets.length !== 0) {
      filteredData = [...filteredData]
      console.debug('filtered data', filteredData)
    }
    if(subCategories && subCategories.length !== 0) {
        const cat = subCategories?.map((category) => {
            if(modifySingleArr(selectedChips).indexOf(Number(category.id)) !== -1) {
                category.selected = true
            } else {
                category.selected = false
            }
            return category
        })
    // console.log('cat is', cat, selectedFilter['category_ids'])
    setCatFacet(cat)
  }
    const removeHVerified = filteredData.filter((el) => el.disabled !== true)
    console.log('removedfilter', removeHVerified)
    setFacetClone(removeHVerified)
  }

  const handleCategoryFilter = (e, attr, option) => {
    const productListing = document.querySelector('.product-category-cards')
    const selectedOption = {
      field: attr,
      value: Number(e.target.value),
      label: option.name
    }
    if (e.target.checked) {
      dispatch(setSelectedFilter(selectedOption))
    } else {
      dispatch(removeSelectedFilter(selectedOption))
    }
    setTimeout(() => {
      if(productListing) {
          productListing?.scrollIntoView({ behavior: 'smooth' })
      }
  }, 500)
  }


  /** checking with facets for persisting filters on mobile  */
  const modifySingleArr = (arr) => {
    const singleArr = []
    for(let item of arr) {
      singleArr.push(item.field === 'is_hm_verified' ? Number(item.value) : item.value)
    }
    return singleArr
  }

  const rangeFormat = {
    to: function(val) {
      return parseInt(val)
    },
    from: function(val) {
      return parseInt(val)
    }
  }

  const formatSelectedPrice = () => {
    if(selectedFilter['price']) {
      setStartValue(selectedFilter['price'].split('_'))
    } else {
      setStartValue([0, maxRange])
    }
  }

  const handleRating = (event, rating) => {
    dispatch(setFilterValues({ 
      field: 'ratings',
      value: event.target.value,
      label: rating.label
     }))
     dispatch(setSelectedFilter({
      field: 'ratings',
      value: event.target.value,
      label: `ratings: ${event.target.value}`
     }))
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
  }

  const clearFilters = () => {
    dispatch(setEmptyFilter())
    hideFilter()
  }

  const applyFilters = () => {
    dispatch(setMobileFilters())
    hideFilter()
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

  useEffect(() => {
    // formatSelectedPrice()
    excludeFacets(JSON.parse(JSON.stringify(facets)))
  }, [facets, selectedChips, maxRange])


  return (
    <div className='mobile-view-facets relative'>
      <div className='mobile-filter-facets block text-gray-100 sm:hidden fixed bottom-0 bg-white h-[50px] w-full z-10 shadow-xl border'>
        <div className='flex items-center h-full'>
          <div className='text-center w-full h-[40px] border-r flex justify-center items-center' onClick={hideFilter}>
            <span className='pl-2 flex w-full h-full justify-center items-center font-semibold'>Close</span>
          </div>
          <div className='text-center w-full text-hyugapurple-500 flex justify-center items-center h-full'>
            <span className='pl-2 flex w-full h-full justify-center items-center font-semibold' onClick={() =>applyFilters(selectedFilter)}>Apply</span>
          </div>
        </div>
      </div>
        <div className='filter-title text-gray-100 px-4 py-3.5 shadow flex items-center'>
            <ArrowLeftIcon onClick={hideFilter} className='h-5 w-5' />
            <p className='text-lg font-semibold pl-3'>Filters</p>
            {Object.keys(excludeQuery()).length > 0 ? <div className='flex items-center justify-end w-full text-base font-semibold cursor-pointer text-hyugapurple-500' onClick={clearFilters}>Clear filters</div>: null }
        </div>

        <Tabs activeIndex={(index) => setTab(index)} tabData={catFacet.length ? [{label: 'Category'},...facetClone]: facetClone} count={true} customData={catFacet}>
          {catFacet?.length !== 0 && <div className={`category-filter-facets ${activeTab === 0 ? 'block' : 'hidden'} border-none rounded-lg mb-5`}>
            <div className={`category-level-filter`}>
              {catFacet.map((category, i) => <div key={i} className={`pb-2 pt-1.5 border-b-[#E7E7E7] sm:border-b-0 sm:pt-0 sm:pb-4 flex items-center justify-between filter-category_${category.name}`}>
                <div className='truncate pr-1'>
                  <FormInput type={'checkbox'} label={category.name} id={category.slug} value={category.id} checked={category?.selected} onChange={(e) => handleCategoryFilter(e, 'category_ids', category)} />
                </div>
                {category?.count && <span className='facet-count text-[#8B8B95] font-normal relative top-[3px] text-sm sm:text-base'>{category?.count}</span>}
              </div>)}
            </div>
          </div>}
          {facetClone.map((facet, index) => {
              return<div key={facet.attribute_code} className={`${activeTab === (catFacet.length ? index + 1 : index) ? 'block' : 'hidden'}`}>
              {(facet.attribute_code !== 'ratings') ? <CategoryFacet isMobile={true} facetData={facet} />: null}
                {/* {facet.attribute_code === 'price' ? <CategoryFacet title={'Price'} isMobile={true} isContent={true}>
                  <div className='flex justify-between text-gray-100 text-base'>
            <span className='range-price-lower-value'>₹{rangeValue.min}</span>
            <span className='range-price-uppe-valuer'>₹{rangeValue.max}</span>
          </div>
          <Nouislider range={{ min: 0, max: maxRange }} start={startValue} format={rangeFormat} connect onUpdate={(e) => onTriggerUpdate(e)}  onChange={(e) => onTriggerChange(e)} className={'price-slider-ui h-2 w-[90%] mb-6 mt-2'} />
                </CategoryFacet> : null} */}
              {/* {
                facet.attribute_code === 'ratings' ? <CategoryFacet isMobile={true} title={'Rating'} isContent={true}>
                {ratings.map((rating) => <div key={rating.value} className='pb-4 flex cursor-pointer'>
                  <FormInput type={'radio'} value={rating.value} id={rating.value} name={rating.name} checked={rating.value === selectedFilter['ratings']} labelStyle={'flex items-center'} onChange={(e) => handleRating(e, 'ratings', rating)}>
                      {Array.from(Array(rating.label), (el, i) => <StarIcon key={i} className='h-6 w-6 pb-1.5' />)}
                      <span className='relative bottom-[3px]'>& Above</span>
                  </FormInput>
                </div>)}
            </CategoryFacet> : null
              } */}
            </div>
          })}
        </Tabs>
    </div>
  )
}

export default MobileFacets