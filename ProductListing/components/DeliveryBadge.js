
import useResize from 'hooks/useResize'
import React from 'react'

const DeliveryBadge = ({ estimatedTime, isHeader, isPdp }) => {
  return (
    <div className={`delivery-badge`}>
      {estimatedTime?.isQuick ?
        <div className={`inline-flex gap-x-[5px] rounded-md ${isHeader ?'p-0':'p-1'}`} style={{ background: 'var(--edd-bg-tag, linear-gradient(90deg, #F1E4FF 0%, #E4E8FF 100%))' }}>
          <img src='/assets/images/header/delivery-vehicle-icon.svg' />
          <span className='uppercase text-xs font-bold text-[#2F41B4]'>Get it {estimatedTime?.time}{isHeader ? '*' : ''}</span>
        </div> :
        <div className='flex items-center gap-x-1'>
          <img src='/assets/images/header/delivery-vehicle-icon.svg' className='mt-1' />
          <p><span className={`text-[#686E73] font-medium ${isHeader ?'text-xs':'text-sm'} sm:text-sm`}>Get it by</span> <span className='text-gray-100 font-semibold text-sm sm:text-base'>{estimatedTime?.time}</span></p>
        </div>}
    </div>
  )
}

export default DeliveryBadge