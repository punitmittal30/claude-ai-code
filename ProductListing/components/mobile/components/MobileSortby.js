import { XIcon } from '@heroicons/react/outline'
import { filterDataLayer } from 'analytics/datalayer/navigate'
import { filterFbq } from 'analytics/fbq/navigate'
import { generateSource } from 'analytics/helpers'
import { filterSelectTracker } from 'analytics/trackers/navigate'
import { setFilterValues } from 'modules/ProductListing/redux/reducer'
import React, { useEffect } from 'react'
import { useDispatch } from 'react-redux'
import FormInput from 'shared/components/FormInput/FormInput'
import { sortOptions } from '../../constants'
import { trackFilterSelect } from 'analytics/plugins/webEngage/navigate'

const trackSortBy = (value) => {
    const trackingData = {
        name: "sort-by", source: generateSource()?.clickSource, value, featureSource: "normal_filters",
    };
    filterDataLayer(trackingData, { featureSource: "normal_filters" });
    filterFbq(trackingData)
    filterSelectTracker({ filter: trackingData });
    trackFilterSelect({ filter: trackingData });
}

const MobileSortby = ({ hideSort, selectedFilter, isSearch }) => {
    const dispatch = useDispatch()
    const handleSort = (event, option) => {
        const productListing = document.querySelector('.product-category-cards')
        dispatch(setFilterValues({ field: 'sort_by', value: option.value }))
        hideSort();
        trackSortBy(option.value);
        setTimeout(() => {
            if(productListing) {
                productListing?.scrollIntoView({ behavior: 'smooth' })
            }
        }, 500)
    }
  return (
    <div className='mobile-sortby-container bg-white rounded-t-xl border-gray-200 border shadow-md'>
        <div className='mobile-sort-title pl-4 py-5 text-lg text-gray-100 border-b font-semibold flex justify-between'>
            <span>Sort By</span>
            <XIcon
                onClick={hideSort}
                className="h-6 w-6 text-gray-100 mr-2 pr-1"
                size={12}
                aria-hidden="true"
            />
        </div>
        <div className='mobile-sort-options pl-4 pt-4'>
            {sortOptions.map((sort, i) => <div key={sort.value} className='pb-4'>
                <FormInput type={'radio'} label={sort.label} id={sort.value} checked={selectedFilter['sort_by'] ? selectedFilter['sort_by'] === sort.value : sort.value === 'recommended'} name="sort" value={sort.value} onChange={(e) =>handleSort(e, sort)} />
            </div> )}
        </div>
    </div>
  )
}

export default MobileSortby