import React, {useEffect} from 'react';
import Image from "next/image";
import FlashTag from "public/assets/images/timerTag.svg";
import TagIcon from "public/assets/images/Tag.svg"
import TagSale from "public/assets/images/icons/deal_of_day-brown.svg"
import useTimer from "hooks/useTimer";

const TimerDeals = ({ isPdp, timer }) => {
    return <>
        <div className={`flex items-center ${isPdp ? 'gap-x-1' : 'gap-x-0.5'} sm:justify-center`}>
            <Image src={FlashTag} alt="tag-sale" />
            <span className={`${isPdp ? 'text-sm sm:text-base text-[#754D1F]' : 'text-sm text-[#C34624]'} font-bold`}>{timer.timer}</span>
        </div>
    </>
}

const DealOfDay = ({ isPdp }) => {
    return <>
        <div className={`flex items-center ${isPdp ? 'gap-x-1' : 'gap-x-[2px]'}`}>
            {isPdp ? <Image src={TagSale} alt="tag-sale" /> : ''}
            <span className={`${isPdp ? 'text-sm sm:text-base text-[#754D1F] font-bold' : 'text-sm sm:text-[13px] text-[#69340E] font-semibold'}`}>Deal of the day</span>
        </div>
    </>
}


const DealsBadges = ({toDate, left, isDeal, isPdp, fromDate, singleLayout}) => {
    const timer = useTimer(toDate)
    const currentDate = new Date().getTime()
    const dateFrom = new Date(fromDate).getTime()
    useEffect(() => {
        if (toDate) {
            timer.countTimer();
        }
    }, [])
    return (
        <>
            {(toDate && timer && timer?.timer && (fromDate ? currentDate >= dateFrom : true)) ? <div
                className={`${left ? 'left-0 sm:left-4 top-1 sm:top-4' : ''} ${isPdp ? 'top-1 static text-[#754D1F] border border-[#754D1F] py-[3px] pl-1' : 'absolute text-[#C34624] pb-[4px] rounded-tl-lg top-[3px] left-[4px]'} inline-block overflow-hidden rounded-[4px]  bg-white  pr-[6px] ${(!isPdp && !singleLayout) ? 'h-[24px] sm:h-[26px] flex items-center' : ''} z-[1]`}>
                    <TimerDeals isPdp={isPdp} timer={timer} />
            </div> : (isDeal) ?
                <div
                    className={`${left ? 'left-0 sm:left-4 top-1 sm:top-4' : ''} ${isPdp ? 'top-1 static rounded-[4px] bg-white border border-[#754D1F] py-[3px] pl-1 pr-[6px] sm:px-[10px]' : 'deals-polygon pr-4 bg-[#FFC36A] px-2 h-6 sm:h-[26px] flex items-center rounded-tl-lg absolute'} overflow-hidden  inline-block ${singleLayout ? 'absolute left-[0px] top-0' : ''} ${(!isPdp && !singleLayout) ? 'flex items-start z-[1]' : ''}`}>
                    <DealOfDay isPdp={isPdp} />
                </div>
                : null}
        </>
    );
};

export default DealsBadges;