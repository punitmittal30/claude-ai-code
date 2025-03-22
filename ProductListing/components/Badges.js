import React, {useMemo} from 'react';
const Badges = ({badgeType, left, isPdp,onFrequentlyBought}) => {
    const type = useMemo(() => {
        if (badgeType?.includes(',')) {
            const temp = badgeType.split(',')
            return temp[0]
        }

        return badgeType
    }, [badgeType])

    return (
        <div className={`${left ? `left-0 sm:left-4 top-0 sm:top-4` : 'left-[-1px] -top-[0.5px]'} ${isPdp ? 'top-1' : 'rounded-half absolute'} ${isPdp ? "flex items-center gap-x-1 bg-[#FFD467] py-[2px] left-[0] sm:py-1 px-2 h-[20px] sm:h-auto sm:px-3 sm:min-w-[105px] z-[1] badge-polygon" : ''}`}>
            {!isPdp ?
                <div>
                    <div className={` relative flex items-center gap-x-1 w-[78px] h-6 sm:h-auto sm:min-w-[88px] z-[1] `}>
                        <img src='/assets/images/prodLabelBg.svg' className={`rounded-tl-lg w-[78px] h-6 sm:h-auto ${onFrequentlyBought ? "sm:min-w-[80px]" : 'sm:min-w-[88px]'}`} />
                    </div>
                    <span className={`text-[#69340E] absolute ${onFrequentlyBought ? 'text-sm top-1 left-2' : 'top-1/2 -translate-y-1/2 left-[47%] -translate-x-1/2 text-sm sm:text-[13px]'} line-clamp-1  z-[1]  font-semibold `}>{type}</span>
                </div> : 
                <span className="text-[#69340E] text-xs sm:text-base font-bold uppercase">{type}</span>}
        </div>
    );
};

export default Badges;