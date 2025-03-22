import Image from 'next/image'
import React, { useState } from 'react'
import MobileSortby from './components/MobileSortby'
import MobileFacets from './components/MobileFacets'

const MobileFilter = ({ filters, selectedFilter, selectedChips, productCount, subCategories, isBrand, isSearch }) => {
    const [showSort, setShowSort] = useState(false)
    const [showFilter, setFilter] = useState(false)

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

      const hideFilter = () => {
        document.querySelector('html').style.overflow = ''
        setFilter(false)
      }

      const hideSort = () => {
        document.querySelector('html').style.overflow = ''
        setShowSort(false)
      }

  return (
    <div className='mobile-facet-container'>
         <div className='mobile-filter-facets block text-gray-100 sm:hidden fixed bottom-0 bg-white h-[50px] w-full z-[1] shadow-xl border'>
            <div className='flex items-center h-full'>
                <div className='text-center w-full h-[40px] border-r flex justify-center items-center' onClick={() => {
                    document.querySelector('html').style.overflow = 'hidden'
                    setShowSort(true)
                }}> 
                    <Image src="/assets/images/icons/sort-by.svg" alt='sort-by-icon' width={18} height={18} className={"mr-1"} />
                    <span className='pl-2 text-base font-semibold'>SORT BY{selectedFilter['sort_by'] ?<span className='h-1.5 w-1.5 bg-hyugapurple-500 rounded-full inline-block ml-1 relative bottom-[2px]'></span>: null}</span>
                </div>
                <div className='text-center w-full flex justify-center items-center h-full' onClick={() => {
                    setFilter(true);
                    document.querySelector('html').style.overflow = 'hidden'
                    }}>
                    <Image src="/assets/images/icons/filter-mobile.svg" alt='filter-mob-icon' width={18} height={18} className={"mr-1"} />
                    <span className='pl-2 text-base font-semibold'>FILTER{Object.keys(excludeQuery()).length !== 0 ?  <span className='h-1.5 w-1.5 bg-hyugapurple-500 rounded-full inline-block ml-1 relative bottom-[2px]'></span>: null}</span>
                </div>
            </div>
        </div>
        {showSort && <div className='fixed z-10 top-0 h-full w-full bg-[rgb(0,0,0,0.6)]' onClick={hideSort}></div>}
        <div className={`transition fixed bottom-0 z-10 w-full ${showSort ? 'translate-y-0' : 'translate-y-[100%]'} ease-in-out duration-300 shadow-[0_1px_28px_rgba(47,47,47,0.1)]`}>
            <MobileSortby selectedFilter={selectedFilter} hideSort={hideSort} isSearch={isSearch} />
        </div>
        <div className={`transition fixed top-0 h-full w-full z-10 bg-white ${showFilter ? 'translate-x-0': 'translate-x-[100%]' } ease-in-out duration-300`}>
            <MobileFacets isBrand={isBrand} facets={filters} subCategories={subCategories} selectedChips={selectedChips} selectedFilter={selectedFilter} hideFilter={hideFilter} />
        </div>
    </div>
  )
}

export default MobileFilter