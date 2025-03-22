import { filterDataLayer } from 'analytics/datalayer/navigate'
import { filterFbq } from 'analytics/fbq/navigate'
import { generateSource } from 'analytics/helpers'
import { filterSelectTracker } from 'analytics/trackers/navigate'
import Image from 'next/image'
import { useRouter } from 'next/router'
import React, { useEffect, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux'
import FormInput from 'shared/components/FormInput/FormInput'
import { removeSelectedFilter, setFilterValues, setSelectedFilter } from '../redux/reducer';
import { trackFilterSelect } from 'analytics/plugins/webEngage/navigate'

const track_filter = (name, value, source, label, action, group) => {
    const trackingData = {
        name, value, source, label, featureSource: "normal_filters", action, group,
    }
    filterDataLayer(trackingData, { featureSource: "normal_filters" });
    filterFbq(trackingData);
    filterSelectTracker({
        filter: trackingData
    });
    trackFilterSelect({
        filter: trackingData
    })
}

const updateSelectedFilters = (selectedFilters, attr, selectedOption) => {
    if (attr === 'price') {
        selectedFilters = selectedFilters.filter(e => e?.field !== selectedOption?.field);
        selectedFilters = [...selectedFilters, selectedOption];
    } else {
        selectedFilters = [...selectedFilters, selectedOption];
    };
    return selectedFilters;
};

const CategoryFacet = ({ isMobile, title, isSearch, isContent, children, facetData, isExpand }) => {
    const dispatch = useDispatch()
    const [expand, setExpand] = useState(isExpand || false)
    const selectedFilters = useSelector((state) => state.category.selectedFilter);
    const handleFilter = (event, attr, facetData) => {
        const productListing = document.querySelector('.product-category-cards')
        const selectedOption = {
            field: attr,
            value: attr === 'is_hm_verified' ? Number(event.target.value.split('_')[0]) : attr !== 'price' ? event.target.value.split('_')[0] : event.target.value,
            label: facetData.label
        }
        if(event.target.checked) {
            if(isMobile) {
                dispatch(setSelectedFilter(selectedOption))
            } else {
                dispatch(setFilterValues(selectedOption))
                dispatch(setSelectedFilter(selectedOption))
            }
            selectedFilters = updateSelectedFilters(selectedFilters, attr, selectedOption);
        } else {
            if(!isMobile) {
                dispatch(setFilterValues({...selectedOption, isRemove: true }))  
            }
            dispatch(removeSelectedFilter(selectedOption))
            selectedFilters = selectedFilters.filter(e => e?.label !== selectedOption?.label);
        }
        track_filter(
            attr,
            event.target.value,
            isSearch ? "search" : generateSource()?.clickSource,
            facetData?.label,
            event.target.checked ? "add" : "remove",
            selectedFilters && selectedFilters.length ? selectedFilters.map((selectedFilter) => { return selectedFilter?.label }) : undefined,
        )
        setTimeout(() => {
            if(productListing) {
                productListing?.scrollIntoView({ behavior: 'smooth' })
            }
        }, 500)
    }

    const facetSearch = (event, label) => {
       const list = document.querySelectorAll(`.filter-category_${label}`)
       if(list.length !== 0) {
        const showList = list?.forEach((el) => {
            if(el.innerText.toLowerCase().includes(event.toLowerCase())) {
                el.style.display = ''
            } else {
                el.style.display = 'none'
            }
        })
       }
    }

    const handleCollapse = () => {
        if(!isMobile) {
            setExpand(prevState => !prevState)
        }
    }

    return (
        <>
            <div className={`category-filter-facets relative ${!isMobile ? 'border' : 'border-none'} rounded-lg mb-5`}>
                {!isMobile ? <div className={`category-title cursor-pointer text-xl text-gray-100 px-6 font-semibold py-4 ${expand ? 'border-b-[1px]' : ''}`} onClick={handleCollapse}>{facetData?.label ? facetData.label : title}</div> : null }
                {!isMobile && <div className={`chevron-icon absolute top-[20px] right-[16px] ${expand ? 'chevron-down-collapse' : 'chevron-up-collapse'}`} onClick={handleCollapse}>
                    <Image
                        src={'/assets/images/icons/chevron-up.svg'}
                        width={12}
                        height={12}
                        alt="chevron-icon"
                     />
                </div> }
                <div className={`category-filter-contents ${(!expand && !isMobile) ? 'hidden' : ''}`}>
                    {(isSearch && facetData?.options.length > 15) ? <div className='relative facet-search sm:px-6 sm:pr-4 sm:pt-5'>
                            <input type={'text'} id={facetData.label} className='h-10 w-full rounded-lg border-[#D8D8EA] text-gray-100 text-sm indent-4' onChange={(e) => facetSearch(e.target.value, facetData.label)} placeholder={`Search by ${facetData?.label ? facetData.label : title}`} />
                            <img src={'/assets/images/facet-search.svg'} className="absolute top-3 left-2 sm:top-8 sm:left-8" width={15} height={15} />
                        </div>: null}
                    <div className={`${!isMobile ? 'px-6 pr-4 pt-5 max-h-[420px] overflow-auto': ''} relative`}>
                        {isContent ? <>{children}</> : <>
                        {facetData?.options?.map((option, i) => <div key={option.value} className={`pb-2 pt-1.5 border-b-[#E7E7E7] sm:border-b-0 sm:pt-0 sm:pb-4 flex items-center justify-between filter-category_${facetData.label}`}>
                            <div className={`truncate pr-1  ${facetData.attribute_code}`}>
                                <FormInput 
                                    type={facetData.attribute_code === 'price' ? 'radio' : 'checkbox'} 
                                    label={option.label} id={facetData.attribute_code === 'price' ? option.value : `${option.value}_${facetData.attribute_code}`} 
                                    value={facetData.attribute_code === 'price' ? option.value : `${option.value}_${facetData.attribute_code}`} 
                                    checked={option?.selected} labelStyle={((facetData.attribute_code === 'is_hl_verified'|| facetData.attribute_code === 'is_hm_verified') && !isMobile) ? 'z-[2] opacity-0  w-full py-1 px-[40px]' : ''} 
                                    onChange={(e) => handleFilter(e, facetData.attribute_code, option)} 
                                />
                                {((facetData.attribute_code === 'is_hl_verified' || facetData.attribute_code === 'is_hm_verified') && !isMobile ) && <div className='absolute top-[24px] left-[50px] cursor-pointer'><Image src={option.icon} width={option.icon.includes('h_metal') ? 98 : 80} height={option.icon.includes('h_metal') ? 20 : 20} alt="tested-icon" /> </div> }
                            </div>
                            {(option?.count && option?.count != 0) ? <span className='facet-count text-[#8B8B95] font-normal relative top-[3px] text-sm sm:text-base'>{option?.count}</span>: null}
                        </div>)}
                        </>}
                    </div>
                </div>
            </div>
        </>
    )
}

export default CategoryFacet